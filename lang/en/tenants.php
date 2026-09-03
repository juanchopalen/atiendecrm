<?php

return [
    'label' => 'Tenant',
    'plural_label' => 'Tenants',
    'navigation_label' => 'Tenants',
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'tax_id' => 'Tax ID',
        'is_active' => 'Active',
        'days_to_pay' => 'Days of notice before expiration',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
    'sections' => [
        'notifications' => 'Notifications',
    ],
    'helpers' => [
        'days_to_pay' => 'Number of days before a policy expires when an automatic email will be sent to the client.',
    ],
];
