<?php
$paths = array_slice(array_filter(array_map('trim', file('/tmp/needed-image-paths.txt') ?: [])), 0, 15);
foreach ($paths as $p) {
    $url = 'https://stg.tyresonline.ae/media/catalog/product/' . $p;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
    curl_exec($ch);
    echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . " $p\n";
    curl_close($ch);
}
