#!/usr/bin/env php
<?php
$paths = [
    '/m/i/michelin_pilot_sport_4_1.jpg',
    '/tyresonline-tyres/FALKEN-ZE914_3.png',
    '/g/r/group_313.png',
    '/auto-accessories-tyresonline/ag-exclusives-red_5_1_.jpg',
];
$bases = [
    'https://d1u7uj1o3a80t8.cloudfront.net/media',
    'https://www.tyresonline.ae/media',
    'https://stg.tyresonline.ae/media',
    'https://tyresonline.ae/media',
];
foreach ($paths as $p) {
    echo "PATH: $p\n";
    foreach ($bases as $base) {
        $url = $base . $p;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "  $code $url\n";
    }
    echo "\n";
}
