<?php

namespace ScienceWorks\HostConnection\Support;

final class Status
{
    public const DISCONNECTED = 'disconnected';
    public const PENDING = 'pending';
    public const CONNECTED = 'connected';
    public const REJECTED = 'rejected';

    public const ALL = [
        self::DISCONNECTED,
        self::PENDING,
        self::CONNECTED,
        self::REJECTED,
    ];

    public static function isLive(?string $status): bool
    {
        return $status === self::CONNECTED;
    }
}
