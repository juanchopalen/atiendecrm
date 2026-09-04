<?php

return [
    'label' => 'Interacción',
    'plural_label' => 'Interacciones',
    'navigation_label' => 'Interacciones',
    'fields' => [
        'channel' => 'Canal',
        'message' => 'Mensaje',
        'user_id' => 'Por',
        'created_at' => 'Fecha',
    ],
    'channels' => [
        'whatsapp' => 'WhatsApp',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'internal_note' => 'Nota interna',
        'in_person' => 'En persona',
    ],
    'empty_state' => 'Sin interacciones',
    'empty_state_description' => 'Crea una interacción para comenzar.',
    'reply_by_whatsapp' => [
        'label' => 'Responder por WhatsApp',
        'message_field' => 'Mensaje',
        'window_open' => 'Ventana de 24 horas abierta — puedes enviar un mensaje libre. Se cierra el :time.',
        'window_closed_body' => 'La ventana de 24 horas está cerrada (han pasado más de 24h desde el último mensaje del cliente). Meta rechazará un mensaje libre; se necesita una plantilla aprobada, que todavía no se puede enviar desde aquí.',
        'window_never_written' => 'Este cliente todavía no ha escrito por WhatsApp, así que no hay ventana abierta para responderle libremente.',
        'window_closed_title' => 'Ventana de 24 horas cerrada',
        'sent_title' => 'Mensaje enviado por WhatsApp',
        'failed_title' => 'No se pudo enviar el mensaje',
    ],
];
