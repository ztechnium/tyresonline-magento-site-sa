<?php
$urls = [
    'https://d3jcy1c5gdp13r.cloudfront.net/media/mageplaza/bannerslider/banner/image/a/r/artboard_2en_4.webp',
    'https://d3cp9qx5vsaqph.cloudfront.net/media/mageplaza/bannerslider/banner/image/a/r/artboard_2en_4.webp',
    'https://d1u7uj1o3a80t8.cloudfront.net/media/mageplaza/bannerslider/banner/image/a/r/artboard_2en_4.webp',
    'https://www.tyresonline.ae/media/mageplaza/bannerslider/banner/image/a/r/artboard_2en_4.webp',
    'https://cdn.tyresonline.sa/media/mageplaza/bannerslider/banner/image/a/r/artboard_2en_4.webp',
];
foreach ($urls as $url) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'method' => 'HEAD']]);
    $headers = @get_headers($url, 1, $ctx);
    $status = is_array($headers) ? ($headers[0] ?? 'fail') : 'fail';
    echo "$status  $url\n";
}
