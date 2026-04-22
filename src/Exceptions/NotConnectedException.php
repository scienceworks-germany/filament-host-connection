<?php

namespace ScienceWorks\HostConnection\Exceptions;

use RuntimeException;

class NotConnectedException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Host is not connected.');
    }
}
