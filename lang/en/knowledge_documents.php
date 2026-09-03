<?php

return [
    'label' => 'Knowledge document',
    'plural_label' => 'Knowledge base',
    'navigation_label' => 'Knowledge base',
    'fields' => [
        'tipo' => 'Type',
        'categoria' => 'Category',
        'titulo' => 'Title',
        'contenido' => 'Content',
        'created_at' => 'Created at',
    ],
    'tipos' => [
        'faq' => 'FAQ',
        'articulo_kb' => 'KB article',
    ],
    'helpers' => [
        'categoria' => 'Used to filter KB articles by topic (e.g. auto, salud, vida). Leave blank for general FAQs.',
        'caracteres_restantes' => ':restantes characters remaining',
    ],
];
