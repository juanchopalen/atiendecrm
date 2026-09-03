<?php

return [
    'label' => 'Ticket',
    'plural_label' => 'Tickets',
    'navigation_label' => 'Tickets',
    'fields' => [
        'client_id' => 'Client',
        'policy_id' => 'Policy',
        'agent_id' => 'Agent',
        'type' => 'Type',
        'subject' => 'Subject',
        'description' => 'Description',
        'priority' => 'Priority',
        'status' => 'Status',
        'closed_at' => 'Closed at',
        'attachments' => 'Attachments',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
    'types' => [
        'siniestro' => 'Claim',
        'consulta' => 'Inquiry',
        'reclamo' => 'Complaint',
        'renovacion' => 'Renewal',
    ],
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'statuses' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'waiting_client' => 'Waiting client',
        'closed' => 'Closed',
    ],
];
