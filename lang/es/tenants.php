<?php

return [
    'label' => 'Correduría',
    'plural_label' => 'Corredurías',
    'navigation_label' => 'Corredurías',
    'fields' => [
        'name' => 'Nombre',
        'slug' => 'Identificador',
        'tax_id' => 'RIF/NIT',
        'is_active' => 'Activo',
        'days_to_pay' => 'Días de aviso antes del vencimiento',
        'created_at' => 'Creado el',
        'updated_at' => 'Actualizado el',
    ],
    'sections' => [
        'notifications' => 'Notificaciones',
    ],
    'helpers' => [
        'days_to_pay' => 'Número de días antes del vencimiento de una póliza en que se enviará un correo automático al cliente.',
    ],
];
