<?php

return [
    'label' => 'Caso',
    'plural_label' => 'Casos',
    'navigation_label' => 'Casos',
    'fields' => [
        'client_id' => 'Cliente',
        'policy_id' => 'Póliza',
        'agent_id' => 'Agente',
        'type' => 'Tipo',
        'subject' => 'Asunto',
        'description' => 'Descripción',
        'priority' => 'Prioridad',
        'status' => 'Estado',
        'closed_at' => 'Fecha de cierre',
        'attachments' => 'Archivos adjuntos',
        'created_at' => 'Creado el',
        'updated_at' => 'Actualizado el',
    ],
    'types' => [
        'siniestro' => 'Siniestro',
        'consulta' => 'Consulta',
        'reclamo' => 'Reclamo',
        'renovacion' => 'Renovación',
    ],
    'priorities' => [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],
    'statuses' => [
        'open' => 'Abierto',
        'in_progress' => 'En progreso',
        'waiting_client' => 'Esperando cliente',
        'closed' => 'Cerrado',
    ],
];
