# filament-host-connection

Shared host-pairing primitives used by [`filament-site-monitor`](https://github.com/scienceworks-germany/filament-site-monitor) and [`filament-zadarma`](https://github.com/scienceworks-germany/filament-zadarma). Extracted so the HTTP handshake, status storage, and Filament settings UI live in one place.

## Install

```bash
composer require scienceworks/filament-host-connection
```

## Usage

### Singleton pairing (one host per app)

1. Define a concrete key/value model:

```php
use ScienceWorks\HostConnection\Models\HostConnectionSetting;

class MyPluginConnectionSetting extends HostConnectionSetting
{
    protected $table = 'my_plugin_connection_settings';
}
```

2. Migrate a `key/value` table for it.

3. Build a `SingletonHostConnection`:

```php
use ScienceWorks\HostConnection\Client\HostConnectionClient;
use ScienceWorks\HostConnection\Storage\SingletonHostConnection;

$connection = new SingletonHostConnection(
    client: app(HostConnectionClient::class),
    settingsModel: MyPluginConnectionSetting::class,
    types: ['my-plugin'],
);

$connection->connect('https://host.example');
$connection->poll();
$connection->isConnected();
$connection->disconnect();
```

### Per-model pairing (multi-tenant)

```php
use ScienceWorks\HostConnection\Concerns\HasHostConnection;

class Tenant extends Model
{
    use HasHostConnection;

    protected function hostConnectionTypes(): array
    {
        return ['my-plugin'];
    }

    protected function hostConnectionColumns(): array
    {
        return [
            'host_url' => 'monitor_host_url',
            'token' => 'monitor_token',
            'status' => 'monitor_status',
            'request_id' => 'monitor_request_id',
        ];
    }
}

$tenant->hostConnection()->connect('https://host.example');
```

### Filament settings section

```php
use ScienceWorks\HostConnection\Support\ConnectionSchema;

public function form(Form $form): Form
{
    return $form->schema([
        ConnectionSchema::make($connection),
    ]);
}
```

## Config

Publish:

```bash
php artisan vendor:publish --tag=host-connection-config
```

Key values (all env-overridable):

| Key | Default | Purpose |
|---|---|---|
| `host-connection.http.connect_timeout` | 10 | connect request timeout (s) |
| `host-connection.http.poll_timeout` | 5 | poll request timeout (s) |
| `host-connection.http.disconnect_timeout` | 5 | disconnect notify timeout (s) |
| `host-connection.endpoints.connect` | `/api/connect` | connect path |
| `host-connection.endpoints.poll` | `/api/connect/status` | poll path |
| `host-connection.endpoints.disconnect` | `/api/connect/disconnect` | disconnect path |
| `host-connection.ui_poll_seconds` | 10 | Filament wire:poll interval |

## License

MIT
