<?php

return [
    'types' => [
        'passport_submitted' => ['icon' => 'bi-person-vcard', 'class' => 'bf-notif-icon--warning'],
        'passport_approved' => ['icon' => 'bi-check-circle-fill', 'class' => 'bf-notif-icon--success'],
        'passport_rejected' => ['icon' => 'bi-x-circle-fill', 'class' => 'bf-notif-icon--danger'],
        'booking_paid' => ['icon' => 'bi-ticket-perforated', 'class' => 'bf-notif-icon--primary'],
        'order_paid' => ['icon' => 'bi-receipt', 'class' => 'bf-notif-icon--primary'],
        'promotion' => ['icon' => 'bi-megaphone-fill', 'class' => 'bf-notif-icon--coral'],
        'announcement' => ['icon' => 'bi-info-circle-fill', 'class' => 'bf-notif-icon--info'],
    ],
    'fallback' => ['icon' => 'bi-bell', 'class' => 'bf-notif-icon--muted'],
];
