<?php

namespace ScienceWorks\HostConnection\Support;

final readonly class ConnectionResult
{
    public function __construct(
        public string $status,
        public ?string $requestId = null,
        public ?string $token = null,
        public ?string $hostUrl = null,
    ) {
    }

    public static function disconnected(): self
    {
        return new self(Status::DISCONNECTED);
    }
}
