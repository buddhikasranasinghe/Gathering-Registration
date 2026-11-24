<?php

$pricePerPerson = (float) env('TICKET_PRICE_PER_PERSON', 0);

return [
    'price_per_person' => $pricePerPerson,
    'price_per_child' => (float) env('TICKET_PRICE_CHILD', 0),
];
