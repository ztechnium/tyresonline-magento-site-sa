<?php
$paths = [
    'car-tyres/bridgestone_duravis_r630.jpg',
    'car-tyres/Morucha.jpg',
    'tyresonline-tyres/yokohama_bluearth_es32.jpg',
];
$bases = ['https://www.tyresonline.ae/media/catalog/product','https://tyresonline.ae/media/catalog/product','https://stg.tyresonline.ae/media/catalog/product'];
foreach ($paths as $p) {
    echo "PATH $p\n";
    foreach ($bases as $b) {
        $url = "$b/$p";
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
        curl_exec($ch);
        echo '  ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " $url\n";
        curl_close($ch);
    }
}
