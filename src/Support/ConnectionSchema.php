<?php

namespace ScienceWorks\HostConnection\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use ScienceWorks\HostConnection\Contracts\HostConnection;
use ScienceWorks\HostConnection\Exceptions\ConnectionException;

/**
 * Reusable Filament v5 schema section for managing a HostConnection.
 *
 * Consumer plugins call ConnectionSchema::section($connectionResolver) and
 * embed the resulting Section inside their own settings page / resource form.
 * The connection resolver is a closure so the section can be built for either
 * a singleton connection or a per-record connection without duplicating code.
 */
class ConnectionSchema
{
    /**
     * @param \Closure(mixed $record=): HostConnection $connectionResolver
     */
    public static function section(
        \Closure $connectionResolver,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = 'heroicon-o-link',
        string $hostField = 'host_url',
    ): Section {
        return Section::make($title ?? __('host-connection::host-connection.form.status_label'))
            ->description($description)
            ->icon($icon)
            ->collapsible()
            ->collapsed(fn ($record = null) => $connectionResolver($record)->isConnected())
            ->schema([
                TextInput::make($hostField)
                    ->label(__('host-connection::host-connection.form.host_url_label'))
                    ->placeholder(__('host-connection::host-connection.form.host_url_placeholder'))
                    ->default(fn ($record = null) => $connectionResolver($record)->url())
                    ->maxLength(255)
                    ->disabled(fn ($record = null) => (
                        $connectionResolver($record)->isConnected()
                        || $connectionResolver($record)->isPending()
                    )),

                Placeholder::make('host_connection_status')
                    ->label(__('host-connection::host-connection.form.status_label'))
                    ->content(fn ($record = null) => new HtmlString(
                        static::statusBadge($connectionResolver($record)->status())
                    )),

                Actions::make([
                    static::connectAction($connectionResolver, $hostField),
                    static::pollAction($connectionResolver),
                    static::disconnectAction($connectionResolver),
                ]),
            ]);
    }

    protected static function connectAction(\Closure $connectionResolver, string $hostField): Action
    {
        return Action::make('host_connect')
            ->label(__('host-connection::host-connection.actions.connect'))
            ->icon('heroicon-o-link')
            ->color('primary')
            ->visible(fn ($record = null) => (
                ! $connectionResolver($record)->isConnected()
                && ! $connectionResolver($record)->isPending()
            ))
            ->action(function ($record = null, ?Get $get = null) use ($connectionResolver, $hostField) {
                $connection = $connectionResolver($record);
                $url = $get ? trim((string) ($get($hostField) ?? '')) : '';
                if ($url === '') {
                    $url = trim((string) ($connection->url() ?? ''));
                }

                if ($url === '') {
                    Notification::make()
                        ->danger()
                        ->title(__('host-connection::host-connection.form.host_url_required'))
                        ->send();
                    return;
                }

                try {
                    $result = $connection->connect($url);
                } catch (ConnectionException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('host-connection::host-connection.notifications.error', ['message' => $e->getMessage()]))
                        ->send();
                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__(
                        'host-connection::host-connection.notifications.' . $result->status,
                        ['host' => $result->hostUrl ?? $url]
                    ))
                    ->send();
            });
    }

    protected static function pollAction(\Closure $connectionResolver): Action
    {
        return Action::make('host_poll')
            ->label(__('host-connection::host-connection.actions.poll'))
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn ($record = null) => $connectionResolver($record)->isPending())
            ->action(function ($record = null) use ($connectionResolver) {
                $connection = $connectionResolver($record);

                try {
                    $result = $connection->poll();
                } catch (ConnectionException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('host-connection::host-connection.notifications.error', ['message' => $e->getMessage()]))
                        ->send();
                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__(
                        'host-connection::host-connection.notifications.' . $result->status,
                        ['host' => $result->hostUrl ?? $connection->url() ?? '']
                    ))
                    ->send();
            });
    }

    protected static function disconnectAction(\Closure $connectionResolver): Action
    {
        return Action::make('host_disconnect')
            ->label(__('host-connection::host-connection.actions.disconnect'))
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('host-connection::host-connection.disconnect_confirm.heading'))
            ->modalDescription(__('host-connection::host-connection.disconnect_confirm.body'))
            ->visible(fn ($record = null) => (
                $connectionResolver($record)->isConnected()
                || $connectionResolver($record)->isPending()
            ))
            ->action(function ($record = null) use ($connectionResolver) {
                $connection = $connectionResolver($record);
                $host = $connection->url();
                $connection->disconnect();

                Notification::make()
                    ->success()
                    ->title(__('host-connection::host-connection.notifications.disconnected', ['host' => $host ?? '']))
                    ->send();
            });
    }

    protected static function statusBadge(string $status): string
    {
        $label = __('host-connection::host-connection.status.' . $status);
        [$dot, $pulse] = match ($status) {
            Status::CONNECTED => ['bg-green-500', ''],
            Status::PENDING => ['bg-yellow-500', 'animate-pulse'],
            Status::REJECTED => ['bg-red-500', ''],
            default => ['bg-gray-400', ''],
        };

        return '<span class="inline-flex items-center gap-1.5" role="status" aria-label="' . e($label) . '">'
            . '<span class="h-2.5 w-2.5 rounded-full ' . $dot . ' ' . $pulse . '" aria-hidden="true"></span>'
            . '<span>' . e($label) . '</span>'
            . '</span>';
    }
}
