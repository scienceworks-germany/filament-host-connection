<?php

namespace ScienceWorks\HostConnection\Exceptions;

use RuntimeException;

class ConnectionException extends RuntimeException
{
    public static function invalidUrl(string $url): self
    {
        return new self("Invalid host URL: {$url}");
    }

    public static function httpFailed(string $action, string $reason): self
    {
        return new self("Host connection {$action} failed: {$reason}");
    }

    public static function unexpectedResponse(string $action): self
    {
        return new self("Host returned an unexpected response during {$action}.");
    }
}
