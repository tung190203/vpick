<?php

return [
    'driver' => env('GEOCODER_DRIVER', 'osm'),

    'google' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'base_url' => 'https://maps.googleapis.com/maps/api/place/autocomplete/json',
        'details_url' => 'https://maps.googleapis.com/maps/api/place/details/json',
    ],

    'osm' => [
        'base_url' => 'https://nominatim.openstreetmap.org/search',
    ],
];
