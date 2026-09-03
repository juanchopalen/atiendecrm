<?php

return [
    'label' => 'Interaction',
    'plural_label' => 'Interactions',
    'navigation_label' => 'Interactions',
    'fields' => [
        'channel' => 'Channel',
        'message' => 'Message',
        'user_id' => 'By',
        'created_at' => 'Created at',
    ],
    'channels' => [
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'phone' => 'Phone',
        'internal_note' => 'Internal note',
        'in_person' => 'In person',
    ],
    'empty_state' => 'No interactions',
    'empty_state_description' => 'Create an interaction to get started.',
];
