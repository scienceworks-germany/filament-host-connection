<?php

namespace ScienceWorks\HostConnection\Contracts;

use ScienceWorks\HostConnection\Support\ConnectionResult;

interface HostConnection
{
    public function url(): ?string;

    public function token(): ?string;

    public function status(): string;

    public function requestId(): ?string;

    public function isConnected(): bool;

    public function isPending(): bool;

    /**
     * Initiate pairing with the host. The host may approve immediately (status
     * "connected") or queue the request for manual approval (status "pending").
     */
    public function connect(string $hostUrl): ConnectionResult;

    /**
     * Poll the host for approval of a pending request.
     */
    public function poll(): ConnectionResult;

    /**
     * Notify the host we are disconnecting and clear local state.
     */
    public function disconnect(): void;
}
