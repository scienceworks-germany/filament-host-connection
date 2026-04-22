<?php

return [
    'status' => [
        'disconnected' => 'Nicht verbunden',
        'pending' => 'Warte auf Freigabe…',
        'connected' => 'Verbunden',
        'rejected' => 'Abgelehnt',
    ],

    'actions' => [
        'connect' => 'Verbinden',
        'disconnect' => 'Trennen',
        'poll' => 'Status prüfen',
    ],

    'form' => [
        'host_url_label' => 'Host-URL',
        'host_url_placeholder' => 'https://example.com',
        'host_url_required' => 'Bitte eine URL eingeben.',
        'status_label' => 'Status',
    ],

    'disconnect_confirm' => [
        'heading' => 'Verbindung trennen?',
        'body' => 'Diese App kommuniziert danach nicht mehr mit dem Host.',
    ],

    'notifications' => [
        'connected' => 'Verbunden mit :host.',
        'pending' => 'Verbindung angefragt — warte auf Freigabe.',
        'rejected' => 'Der Host hat die Verbindung abgelehnt.',
        'disconnected' => 'Verbindung zu :host getrennt.',
        'error' => 'Verbindungsfehler: :message',
    ],
];
