<?php

return [
    'label' => 'Policy',
    'plural_label' => 'Policies',
    'navigation_label' => 'Policies',
    'fields' => [
        'client_id' => 'Client',
        'policy_number' => 'Policy number',
        'line_of_business' => 'Line of business',
        'insurer' => 'Insurer',
        'start_date' => 'Start date',
        'expiration_date' => 'Expiration date',
        'status' => 'Status',
        'premium' => 'Premium',
        'payment_frequency' => 'Payment frequency',
        'attachments' => 'Attachments',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
    'lines_of_business' => [
        'auto' => 'Auto',
        'salud' => 'Health',
        'vida' => 'Life',
        'hogar' => 'Home',
        'otro' => 'Other',
    ],
    'statuses' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],
    'payment_frequencies' => [
        'mensual' => 'Monthly',
        'trimestral' => 'Quarterly',
        'semestral' => 'Semiannual',
        'anual' => 'Annual',
    ],
    'filters' => [
        'expiring_soon' => 'Expiring in next 30 days',
    ],
];
