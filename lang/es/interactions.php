<?php

return [
    'label' => 'Interacción',
    'plural_label' => 'Interacciones',
    'navigation_label' => 'Interacciones',
    'fields' => [
        'channel' => 'Canal',
        'message' => 'Mensaje',
        'user_id' => 'Por',
        'created_at' => 'Fecha',
    ],
    'channels' => [
        'whatsapp' => 'WhatsApp',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'internal_note' => 'Nota interna',
        'in_person' => 'En persona',
    ],
    'empty_state' => 'Sin interacciones',
    'empty_state_description' => 'Crea una interacción para comenzar.',
];
