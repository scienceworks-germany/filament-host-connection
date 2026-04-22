<?php

namespace ScienceWorks\HostConnection\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Abstract key/value store for a singleton host pairing. Concrete subclasses
 * set the $table so each consumer plugin can keep its own settings table
 * (avoids cross-plugin collisions without needing a discriminator column).
 */
abstract class HostConnectionSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = false;

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $record = static::query()->where('key', $key)->first();
        } catch (Throwable $e) {
            Log::warning('host-connection: read failed', [
                'table' => (new static)->getTable(),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }

        return $record?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value === null) {
            static::forget($key);
            return;
        }

        try {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        } catch (Throwable $e) {
            Log::warning('host-connection: write failed', [
                'table' => (new static)->getTable(),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function forget(string $key): void
    {
        try {
            static::query()->where('key', $key)->delete();
        } catch (Throwable $e) {
            Log::warning('host-connection: delete failed', [
                'table' => (new static)->getTable(),
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
