<?php
declare(strict_types=1);
/**
 * Analyze why KSA products fail to match UAE keys - size normalization issues.
 */
$ksa = json_decode(file_get_contents('/tmp/ksa-products-keys.json'), true);
$uae = json_decode(file_get_contents('/tmp/uae-images-by-key.json'), true);
$uaeKeys = array_column($uae, 'match_key');
$uaeSet = array_flip($uaeKeys);

function normSize(string $s): string {
    $s = strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    $s = str_replace(['zr', ' z ', 'r '], [' r ', ' r ', 'r'], $s);
    $s = preg_replace('/\s+/', '', $s);
    return $s;
}

$miss = 0;
$fixable = 0;
$samples = [];
foreach ($ksa as $p) {
    if (isset($uaeSet[$p['match_key']])) continue;
    $miss++;
    $parts = explode('|', $p['match_key']);
    $brand = $parts[0] ?? '';
    $size = $parts[1] ?? '';
    // try find UAE key with same brand and normalized size
    foreach ($uaeKeys as $uk) {
        [$ub, $us] = explode('|', $uk, 2) + ['', ''];
        if ($ub === $brand && normSize($us) === normSize($size)) {
            $fixable++;
            if (count($samples) < 5) $samples[] = "{$p['sku']}: {$p['match_key']} => $uk";
            break;
        }
    }
}
echo "ksa_unmatched=$miss fixable_by_size_norm=$fixable\n";
foreach ($samples as $s) echo "  $s\n";
