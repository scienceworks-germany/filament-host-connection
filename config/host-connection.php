<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP timeouts (seconds)
    |--------------------------------------------------------------------------
    */
    'http' => [
        'connect_timeout' => env('HOST_CONNECTION_CONNECT_TIMEOUT', 10),
        'poll_timeout' => env('HOST_CONNECTION_POLL_TIMEOUT', 5),
        'disconnect_timeout' => env('HOST_CONNECTION_DISCONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints on the host (appended to the paired host URL)
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'connect' => '/api/connect',
        'poll' => '/api/connect/status',
        'disconnect' => '/api/connect/disconnect',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI polling interval (seconds) for Filament ConnectionSchema wire:poll
    |--------------------------------------------------------------------------
    */
    'ui_poll_seconds' => env('HOST_CONNECTION_UI_POLL_SECONDS', 10),

];
