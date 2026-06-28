<?php
foreach (['en', 'ar'] as $store) {
    $url = "https://stg.tyresonline.sa/$store/alpha-185701488aggressor-zp01-2025.html";
    $html = @file_get_contents($url);
    if ($html === false) {
        echo "$store: FETCH FAILED\n";
        continue;
    }
    echo "=== $store len=" . strlen($html) . " ===\n";
    preg_match_all('#(?:href|src)=["\']([^"\']+\.(css|js))[^"\']*["\']#i', $html, $m);
    $assets = array_unique($m[1]);
    $broken = 0;
    foreach (array_slice($assets, 0, 15) as $a) {
        if (str_starts_with($a, '//')) $a = 'https:' . $a;
        elseif (str_starts_with($a, '/')) $a = 'https://stg.tyresonline.sa' . $a;
        $h = @get_headers($a, true);
        $code = is_array($h) ? (int)(is_array($h[0] ?? null) ? end($h[0]) : ($h[0] ?? 0)) : 0;
        if (preg_match('#HTTP/\S+ (\d+)#', (string)($h[0] ?? ''), $cm)) $code = (int)$cm[1];
        if ($code >= 400) { $broken++; echo "  FAIL $code $a\n"; }
    }
    echo "sample_assets=" . count($assets) . " broken_in_sample=$broken\n";
    if (preg_match('#<html[^>]*dir=["\']([^"\']+)#i', $html, $dir)) echo "dir={$dir[1]}\n";
    if (preg_match('#<html[^>]*lang=["\']([^"\']+)#i', $html, $lang)) echo "lang={$lang[1]}\n";
    if (preg_match('#baseUrl[^;]*["\']([^"\']+)#', $html, $base)) echo "base={$base[1]}\n";
    if (preg_match('#require\s*=\s*\{[^}]*baseUrl[^}]*\}', $html, $req)) echo substr($req[0], 0, 120) . "\n";
    echo "\n";
}
