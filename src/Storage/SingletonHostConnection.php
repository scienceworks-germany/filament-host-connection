<?php

namespace ScienceWorks\HostConnection\Storage;

use ScienceWorks\HostConnection\Client\HostConnectionClient;
use ScienceWorks\HostConnection\Contracts\HostConnection;
use ScienceWorks\HostConnection\Models\HostConnectionSetting;
use ScienceWorks\HostConnection\Support\ConnectionResult;
use ScienceWorks\HostConnection\Support\Status;

/**
 * App-wide singleton pairing backed by a key/value settings table. Use this
 * when a Laravel app pairs once and the whole app speaks to one host.
 *
 * For multi-tenant pairings (one pairing per tenant) use ModelHostConnection.
 */
class SingletonHostConnection implements HostConnection
{
    /**
     * @param class-string<HostConnectionSetting> $settingsModel
     * @param array<int, string> $types
     */
    public function __construct(
        protected HostConnectionClient $client,
        protected string $settingsModel,
        protected array $types,
    ) {
    }

    public function url(): ?string
    {
        return $this->read('host_url');
    }

    public function token(): ?string
    {
        return $this->read('token');
    }

    public function status(): string
    {
        return $this->read('status') ?? Status::DISCONNECTED;
    }

    public function requestId(): ?string
    {
        return $this->read('request_id');
    }

    public function isConnected(): bool
    {
        return Status::isLive($this->status());
    }

    public function isPending(): bool
    {
        return $this->status() === Status::PENDING;
    }

    public function connect(string $hostUrl): ConnectionResult
    {
        $result = $this->client->connect($hostUrl, $this->types);

        $this->write('host_url', $result->hostUrl);
        $this->write('status', $result->status);
        $this->write('request_id', $result->requestId);
        $this->write('token', $result->token);

        return $result;
    }

    public function poll(): ConnectionResult
    {
        $host = $this->url();
        $requestId = $this->requestId();

        if ($host === null || $requestId === null) {
            return ConnectionResult::disconnected();
        }

        $result = $this->client->poll($host, $requestId, $this->token());

        $this->write('status', $result->status);
        if ($result->token !== null) {
            $this->write('token', $result->token);
        }

        return $result;
    }

    public function disconnect(): void
    {
        $this->client->disconnect($this->url() ?? '', $this->token());

        $this->forget('host_url');
        $this->forget('token');
        $this->forget('status');
        $this->forget('request_id');
    }

    protected function read(string $key): ?string
    {
        return $this->settingsModel::get($key);
    }

    protected function write(string $key, ?string $value): void
    {
        $this->settingsModel::put($key, $value);
    }

    protected function forget(string $key): void
    {
        $this->settingsModel::forget($key);
    }
}
