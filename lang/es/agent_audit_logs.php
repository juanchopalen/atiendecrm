<?php

return [
    'label' => 'Registro del agente',
    'plural_label' => 'Auditoría del agente',
    'navigation_label' => 'Auditoría del agente',
    'fields' => [
        'created_at' => 'Fecha',
        'canal' => 'Canal',
        'telefono' => 'Teléfono',
        'client_id' => 'Cliente',
        'mensaje' => 'Mensaje',
        'tipo_intencion' => 'Intención',
        'confianza' => 'Confianza',
        'fuente' => 'Fuente',
        'tool_calls' => 'Herramientas usadas',
        'requiere_seguimiento_humano' => 'Requiere seguimiento humano',
        'error' => 'Error técnico',
        'tiene_error' => 'Sin errores',
    ],
    'filters' => [
        'con_error' => 'Con error técnico',
    ],
];
