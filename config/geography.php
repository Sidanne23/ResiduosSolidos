<?php

return [
    'boundary_file' => resource_path('geo/amarilis.geojson'),
    'geocoder_url' => env('GEOCODER_URL', 'https://nominatim.openstreetmap.org/search'),
    'geocoder_agent' => 'AmarilisAmbiental-Local/1.0 (interactive place search)',
    'tile_url' => env('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'tile_attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
];
