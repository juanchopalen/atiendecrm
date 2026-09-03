<?php

return [
    'label' => 'Número de WhatsApp',
    'plural_label' => 'Números de WhatsApp',
    'navigation_label' => 'Números de WhatsApp',
    'fields' => [
        'whatsapp_business_account_id' => 'Cuenta de WhatsApp Business',
        'phone_number_id' => 'ID de número (Meta)',
        'numero_visible' => 'Número',
        'departamento' => 'Departamento',
        'modo' => 'Modo',
        'estado' => 'Estado',
        'calidad' => 'Calidad',
        'solo_demo' => 'Solo demo',
    ],
    'helpers' => [
        'phone_number_id' => 'ID del número asignado por Meta al conectar vía Embedded Signup.',
        'departamento' => 'Ej. Suscripción, Cobranzas, General.',
        'solo_demo' => 'Bloquea el uso de este canal en flujos de notificación de producción.',
    ],
    'modos' => [
        'dedicated' => 'Dedicado',
        'coexistence' => 'Coexistencia',
    ],
    'estados' => [
        'active' => 'Activo',
        'pending_verification' => 'Pendiente de verificación',
        'disabled' => 'Deshabilitado',
    ],
    'calidades' => [
        'green' => 'Verde',
        'yellow' => 'Amarillo',
        'red' => 'Rojo',
        'unknown' => 'Desconocida',
    ],
];
