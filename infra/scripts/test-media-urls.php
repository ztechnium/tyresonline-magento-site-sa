<?php
declare(strict_types=1);
/**
 * Test multiple media URL bases for UAE image paths.
 */
$paths = [
    '/car-tyres/yokohama_bluearth_es32.jpg',
    '/tyresonline-tyres/yokohama_bluearth_es32.jpg',
    '/m/i/michelin_pilot_sport_4_1.jpg',
    '/catalog/product/car-tyres/yokohama_bluearth_es32.jpg',
];
$bases = [
    'https://www.tyresonline.ae/media',
    'https://tyresonline.ae/media',
    'https://stg.tyresonline.ae/media',
    'https://d1u7uj1o3a80t8.cloudfront.net/media',
    'https://stg.tyresonline.sa/media',
];
foreach ($paths as $p) {
    echo "PATH $p\n";
    foreach ($bases as $base) {
        $url = $base . $p;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_TIMEOUT=>10]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) echo "  OK $url\n";
        else echo "  $code $url\n";
    }
    echo "\n";
}
