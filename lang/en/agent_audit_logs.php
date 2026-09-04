<?php

return [
    'label' => 'Agent log',
    'plural_label' => 'Agent audit log',
    'navigation_label' => 'Agent audit log',
    'fields' => [
        'created_at' => 'Date',
        'canal' => 'Channel',
        'telefono' => 'Phone',
        'client_id' => 'Client',
        'mensaje' => 'Message',
        'tipo_intencion' => 'Intent',
        'confianza' => 'Confidence',
        'fuente' => 'Source',
        'tool_calls' => 'Tools used',
        'requiere_seguimiento_humano' => 'Needs human follow-up',
        'error' => 'Technical error',
        'tiene_error' => 'No errors',
    ],
    'filters' => [
        'con_error' => 'With technical error',
    ],
];
