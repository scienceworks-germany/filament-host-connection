<?php

namespace ScienceWorks\HostConnection\Storage;

use Illuminate\Database\Eloquent\Model;
use ScienceWorks\HostConnection\Client\HostConnectionClient;
use ScienceWorks\HostConnection\Contracts\HostConnection;
use ScienceWorks\HostConnection\Support\ConnectionResult;
use ScienceWorks\HostConnection\Support\Status;

/**
 * Per-model pairing backed by columns on a tenant/account Eloquent model.
 * The caller supplies a column map so legacy schemas can be adopted without
 * renaming columns.
 */
class ModelHostConnection implements HostConnection
{
    /**
     * @var array<string, string> logical key => actual column name
     */
    protected array $columns = [
        'host_url' => 'host_url',
        'token' => 'token',
        'status' => 'status',
        'request_id' => 'request_id',
    ];

    /**
     * @param array<int, string> $types
     * @param array<string, string> $columns
     */
    public function __construct(
        protected HostConnectionClient $client,
        protected Model $model,
        protected array $types,
        array $columns = [],
    ) {
        $this->columns = array_merge($this->columns, $columns);
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

        $this->model->forceFill([
            $this->columns['host_url'] => $result->hostUrl,
            $this->columns['status'] => $result->status,
            $this->columns['request_id'] => $result->requestId,
            $this->columns['token'] => $result->token,
        ])->save();

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

        $updates = [$this->columns['status'] => $result->status];
        if ($result->token !== null) {
            $updates[$this->columns['token']] = $result->token;
        }
        $this->model->forceFill($updates)->save();

        return $result;
    }

    public function disconnect(): void
    {
        $this->client->disconnect($this->url() ?? '', $this->token());

        $this->model->forceFill([
            $this->columns['host_url'] => null,
            $this->columns['token'] => null,
            $this->columns['status'] => Status::DISCONNECTED,
            $this->columns['request_id'] => null,
        ])->save();
    }

    protected function read(string $key): ?string
    {
        $column = $this->columns[$key] ?? $key;
        $value = $this->model->getAttribute($column);

        return $value === null ? null : (string) $value;
    }
}
