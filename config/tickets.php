<?php

return [
    'price_per_person' => (float) env('TICKET_PRICE_PER_PERSON', 0),
    'price_per_child' => (float) env('TICKET_PRICE_CHILD', 0),
    'storage_disk' => env('TICKET_STORAGE_DISK', 'local'),
    'number_prefix' => env('TICKET_NUMBER_PREFIX', 'GATH'),
    'number_padding' => 5,
    'qr_size' => 320,
    'template_path' => resource_path('views/tickets/ticket-template.jpg'),
];
