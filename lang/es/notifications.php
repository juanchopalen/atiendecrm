<?php

return [
    'policy_expiring_soon' => [
        'subject' => 'Tu póliza :policy_number vence pronto',
        'greeting' => 'Hola :name,',
        'line_1' => 'Te informamos que tu póliza :policy_number (:line_of_business) con :insurer vence el :expiration_date.',
        'line_2' => 'Faltan :days días para su vencimiento. Te recomendamos contactar a tu agente para gestionar el pago o la renovación a tiempo.',
        'action' => 'Ver detalles',
        'salutation' => 'Saludos,',
    ],
    'agent_escalation' => [
        'subject' => 'Seguimiento requerido: :client_name',
        'greeting' => 'Hola :name,',
        'line_1' => 'El agente automático no pudo resolver una consulta de :client_name y necesita seguimiento humano.',
        'line_2' => 'Mensaje del cliente: ":mensaje"',
        'action' => 'Ver ticket',
        'line_3' => 'Motivo: :motivo',
    ],
];
