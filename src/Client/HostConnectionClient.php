<?php

namespace ScienceWorks\HostConnection\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ScienceWorks\HostConnection\Exceptions\ConnectionException;
use ScienceWorks\HostConnection\Support\ConnectionResult;
use ScienceWorks\HostConnection\Support\Status;
use Throwable;

/**
 * Pure HTTP transport for host pairing. Holds no state — callers pass in the
 * host URL and token, and the client issues the connect/poll/disconnect calls.
 *
 * Keeping this class state-free means both singleton storage and per-model
 * storage can share one HTTP implementation; bugs get fixed in one place.
 */
class HostConnectionClient
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected array $config = [],
    ) {
    }

    public function connect(string $hostUrl, array $types, array $extra = []): ConnectionResult
    {
        $hostUrl = $this->normalizeUrl($hostUrl);
        $endpoint = $hostUrl . $this->endpoint('connect');

        try {
            $response = Http::timeout($this->timeout('connect'))
                ->acceptJson()
                ->asJson()
                ->post($endpoint, array_merge([
                    'name' => config('app.name'),
                    'url' => config('app.url'),
                    'types' => array_values($types),
                ], $extra));
        } catch (Throwable $e) {
            throw ConnectionException::httpFailed('connect', $e->getMessage());
        }

        if (! $response->successful()) {
            throw ConnectionException::httpFailed('connect', "HTTP {$response->status()}");
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw ConnectionException::unexpectedResponse('connect');
        }

        return new ConnectionResult(
            status: $this->normalizeStatus($data['status'] ?? Status::PENDING),
            requestId: $data['request_id'] ?? null,
            token: $data['token'] ?? null,
            hostUrl: $hostUrl,
        );
    }

    public function poll(string $hostUrl, ?string $requestId, ?string $token = null): ConnectionResult
    {
        if ($requestId === null || $requestId === '') {
            return ConnectionResult::disconnected();
        }

        $hostUrl = $this->normalizeUrl($hostUrl);
        $endpoint = $hostUrl . $this->endpoint('poll');

        try {
            $request = Http::timeout($this->timeout('poll'))
                ->acceptJson()
                ->asJson();

            if ($token !== null && $token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($endpoint, ['request_id' => $requestId]);
        } catch (Throwable $e) {
            throw ConnectionException::httpFailed('poll', $e->getMessage());
        }

        if (! $response->successful()) {
            throw ConnectionException::httpFailed('poll', "HTTP {$response->status()}");
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw ConnectionException::unexpectedResponse('poll');
        }

        return new ConnectionResult(
            status: $this->normalizeStatus($data['status'] ?? Status::PENDING),
            requestId: $data['request_id'] ?? $requestId,
            token: $data['token'] ?? $token,
            hostUrl: $hostUrl,
        );
    }

    public function disconnect(string $hostUrl, ?string $token): void
    {
        if ($hostUrl === '' || $token === null || $token === '') {
            return;
        }

        $hostUrl = $this->normalizeUrl($hostUrl);
        $endpoint = $hostUrl . $this->endpoint('disconnect');

        try {
            Http::timeout($this->timeout('disconnect'))
                ->acceptJson()
                ->asJson()
                ->withToken($token)
                ->post($endpoint);
        } catch (Throwable $e) {
            Log::warning('host-connection: disconnect notification failed', [
                'host' => $hostUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            throw ConnectionException::invalidUrl($url);
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw ConnectionException::invalidUrl($url);
        }

        if (! empty($parts['user']) || ! empty($parts['pass'])) {
            throw ConnectionException::invalidUrl($url);
        }

        return rtrim($url, '/');
    }

    protected function timeout(string $action): int
    {
        return (int) ($this->config['http'][$action . '_timeout']
            ?? config('host-connection.http.' . $action . '_timeout', 10));
    }

    protected function endpoint(string $action): string
    {
        return (string) ($this->config['endpoints'][$action]
            ?? config('host-connection.endpoints.' . $action, '/api/connect'));
    }

    protected function normalizeStatus(string $status): string
    {
        $status = strtolower($status);

        return match ($status) {
            'approved', 'connected' => Status::CONNECTED,
            'rejected', 'denied' => Status::REJECTED,
            'pending', 'requested', 'waiting' => Status::PENDING,
            default => in_array($status, Status::ALL, true) ? $status : Status::PENDING,
        };
    }
}
