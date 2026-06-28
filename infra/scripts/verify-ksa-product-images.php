<?php
$html = file_get_contents('https://stg.tyresonline.sa/en/car-tyres.html');
preg_match_all('#/media/catalog/product/[^"\']+\.(jpg|png|webp)#i', $html, $m);
$urls = array_unique($m[0]);
echo 'image_urls_found=' . count($urls) . "\n";
foreach (array_slice($urls, 0, 5) as $u) {
    echo $u . "\n";
}
