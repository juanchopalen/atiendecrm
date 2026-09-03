<?php

return [
    'label' => 'Póliza',
    'plural_label' => 'Pólizas',
    'navigation_label' => 'Pólizas',
    'fields' => [
        'client_id' => 'Cliente',
        'policy_number' => 'Número de póliza',
        'line_of_business' => 'Ramo',
        'insurer' => 'Aseguradora',
        'start_date' => 'Fecha de inicio',
        'expiration_date' => 'Fecha de vencimiento',
        'status' => 'Estado',
        'premium' => 'Prima',
        'payment_frequency' => 'Frecuencia de pago',
        'attachments' => 'Archivos adjuntos',
        'created_at' => 'Creado el',
        'updated_at' => 'Actualizado el',
    ],
    'lines_of_business' => [
        'auto' => 'Auto',
        'salud' => 'Salud',
        'vida' => 'Vida',
        'hogar' => 'Hogar',
        'otro' => 'Otro',
    ],
    'statuses' => [
        'active' => 'Activa',
        'expired' => 'Vencida',
        'cancelled' => 'Cancelada',
    ],
    'payment_frequencies' => [
        'mensual' => 'Mensual',
        'trimestral' => 'Trimestral',
        'semestral' => 'Semestral',
        'anual' => 'Anual',
    ],
    'filters' => [
        'expiring_soon' => 'Por vencer en los próximos 30 días',
    ],
];
