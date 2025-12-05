<?php

return [
    'driver' => env('GEOCODER_DRIVER', 'osm'),

    'google' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'autocomplete_url' => 'https://places.googleapis.com/v1/places:autocomplete',
        'details_url' => 'https://places.googleapis.com/v1/places/',
    ],

    'osm' => [
        'base_url' => 'https://nominatim.openstreetmap.org/search',
    ],
];
