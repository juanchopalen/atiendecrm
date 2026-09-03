<?php

return [
    'label' => 'Cuenta de WhatsApp Business',
    'plural_label' => 'Cuentas de WhatsApp Business',
    'navigation_label' => 'Cuentas WABA',
    'fields' => [
        'waba_id' => 'WABA ID',
        'business_verification_status' => 'Estado de verificación',
        'access_token' => 'Token de acceso',
        'channels_count' => 'Números conectados',
        'created_at' => 'Creado el',
    ],
    'helpers' => [
        'waba_id' => 'ID de la cuenta de WhatsApp Business asignado por Meta.',
        'access_token' => 'Token del Usuario del Sistema de Ademia con permisos otorgados vía Embedded Signup.',
    ],
    'statuses' => [
        'pending' => 'Pendiente',
        'verified' => 'Verificada',
    ],
];
