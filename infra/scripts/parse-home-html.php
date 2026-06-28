#!/usr/bin/env php
<?php
declare(strict_types=1);
$html = file_get_contents('/tmp/home.html');
if (preg_match('/<body[^>]*class="([^"]*)"/', $html, $m)) {
    echo "body_class: {$m[1]}\n";
}
if (preg_match('/<title>([^<]*)<\/title>/', $html, $m)) {
    echo "title: {$m[1]}\n";
}
echo 'cms-index-index: ' . substr_count($html, 'cms-index-index') . "\n";
echo 'Compare Now: ' . substr_count($html, 'Compare Now') . "\n";
echo 'shop-tyre: ' . substr_count($html, 'shop-tyre') . "\n";
echo 'home_main_banner: ' . substr_count($html, 'home_main_banner') . "\n";
echo 'pagebuilder rows: ' . substr_count($html, 'data-content-type="row"') . "\n";
if (preg_match('/<div class="column main[^"]*">(.*?)<\/div>\s*<\/div>\s*<\/main>/s', $html, $m)) {
    echo 'column_main_len: ' . strlen($m[1]) . "\n";
    echo 'column_main_text_sample: ' . substr(strip_tags($m[1]), 0, 200) . "\n";
}
