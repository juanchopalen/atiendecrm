<?php

return [
    'label' => 'Documento de conocimiento',
    'plural_label' => 'Base de conocimiento',
    'navigation_label' => 'Base de conocimiento',
    'fields' => [
        'tipo' => 'Tipo',
        'categoria' => 'Categoría',
        'titulo' => 'Título',
        'contenido' => 'Contenido',
        'created_at' => 'Creado el',
    ],
    'tipos' => [
        'faq' => 'FAQ',
        'articulo_kb' => 'Artículo KB',
    ],
    'helpers' => [
        'categoria' => 'Se usa para filtrar artículos de KB por tema (ej. auto, salud, vida). Déjalo vacío para FAQs generales.',
        'caracteres_restantes' => ':restantes caracteres restantes',
    ],
];
