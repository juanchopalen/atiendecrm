<?php

return [
    'policy_expiring_soon' => [
        'subject' => 'Your policy :policy_number is expiring soon',
        'greeting' => 'Hello :name,',
        'line_1' => 'Your policy :policy_number (:line_of_business) with :insurer expires on :expiration_date.',
        'line_2' => 'There are :days days left until expiration. We recommend contacting your agent to arrange payment or renewal in time.',
        'action' => 'View details',
        'salutation' => 'Regards,',
    ],
    'agent_escalation' => [
        'subject' => 'Follow-up needed: :client_name',
        'greeting' => 'Hello :name,',
        'line_1' => 'The automated agent could not resolve a question from :client_name and needs human follow-up.',
        'line_2' => 'Client message: ":mensaje"',
        'action' => 'View ticket',
        'line_3' => 'Reason: :motivo',
    ],
];
