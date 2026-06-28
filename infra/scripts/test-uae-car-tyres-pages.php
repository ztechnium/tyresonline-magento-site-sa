<?php
for ($p = 1; $p <= 5; $p++) {
    $url = "https://stg.tyresonline.ae/en/all-tyres/car-tyres.html?p=$p";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $len = is_string($body) ? strlen($body) : 0;
    echo "p=$p code=$code len=$len err=$err\n";
    if ($code >= 500 && is_string($body)) {
        echo substr($body, 0, 500) . "\n";
    }
}
