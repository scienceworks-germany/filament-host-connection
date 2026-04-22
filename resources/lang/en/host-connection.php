<?php

return [
    'status' => [
        'disconnected' => 'Not connected',
        'pending' => 'Waiting for approval…',
        'connected' => 'Connected',
        'rejected' => 'Rejected',
    ],

    'actions' => [
        'connect' => 'Connect',
        'disconnect' => 'Disconnect',
        'poll' => 'Check status',
    ],

    'form' => [
        'host_url_label' => 'Host URL',
        'host_url_placeholder' => 'https://example.com',
        'host_url_required' => 'Please enter a URL.',
        'status_label' => 'Status',
    ],

    'disconnect_confirm' => [
        'heading' => 'Disconnect?',
        'body' => 'This app will stop communicating with the host.',
    ],

    'notifications' => [
        'connected' => 'Connected to :host.',
        'pending' => 'Connection requested — waiting for approval.',
        'rejected' => 'The host rejected this connection.',
        'disconnected' => 'Disconnected from :host.',
        'error' => 'Connection error: :message',
    ],
];
