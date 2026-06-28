<?php
foreach (['en' => '/tmp/pdp_en.html', 'ar' => '/tmp/pdp_ar.html'] as $label => $file) {
    if (!is_file($file)) {
        $url = "https://stg.tyresonline.sa/$label/alpha-185701488aggressor-zp01-2025.html";
        file_put_contents($file, file_get_contents($url));
    }
    $html = file_get_contents($file);
    echo "=== $label ===\n";
    preg_match_all('#(?:href|src)=["\']([^"\']+\.css[^"\']*)["\']#i', $html, $m);
    $css = array_unique($m[1]);
    echo 'css_count=' . count($css) . "\n";
    foreach (array_slice($css, 0, 12) as $u) {
        if (str_starts_with($u, '//')) $full = 'https:' . $u;
        elseif (str_starts_with($u, '/')) $full = 'https://stg.tyresonline.sa' . $u;
        else $full = $u;
        $ch = curl_init($full);
        curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "  $code " . substr($full, 0, 120) . "\n";
    }
    preg_match('#<html[^>]*>#i', $html, $tag);
    echo 'html_tag=' . ($tag[0] ?? 'none') . "\n";
    preg_match('#require\s*=\s*\{[^}]+baseUrl[^}]+\}#s', $html, $req);
    if ($req) echo 'require=' . preg_replace('/\s+/', ' ', substr($req[0], 0, 200)) . "\n";
    echo "\n";
}
