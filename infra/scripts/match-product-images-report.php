#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Match KSA products to UAE images; report key overlap and on-disk file availability.
 *
 * Usage on KSA server:
 *   sudo -u www-data php match-product-images-report.php \
 *     /tmp/ksa-products-keys.json /tmp/uae-images-by-key.json
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php match-product-images-report.php ksa-keys.json uae-images.json\n");
    exit(1);
}

$mediaRoot = '/var/www/magento/pub/media';

function magentoMediaExists(string $mediaRoot, string $file): bool
{
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return is_file(rtrim($mediaRoot, '/') . '/' . $file);
    }
    return is_file(rtrim($mediaRoot, '/') . '/catalog/product/' . $file);
}

$ksaProducts = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$uaeImages = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);

$uaeByKey = [];
foreach ($uaeImages as $row) {
    $uaeByKey[$row['match_key']] = $row;
}

$matched = 0;
$missingKey = 0;
$matchedWithFile = 0;
$matchedNoFile = 0;
$missingKeys = [];

foreach ($ksaProducts as $p) {
    if (!isset($uaeByKey[$p['match_key']])) {
        $missingKey++;
        if (count($missingKeys) < 10) {
            $missingKeys[] = $p['sku'] . ' => ' . $p['match_key'];
        }
        continue;
    }

    $matched++;
    $images = $uaeByKey[$p['match_key']]['images'] ?? [];
    $hasFile = false;
    foreach ($images as $img) {
        if (magentoMediaExists($mediaRoot, $img['file'])) {
            $hasFile = true;
            break;
        }
    }
    if ($hasFile) {
        $matchedWithFile++;
    } else {
        $matchedNoFile++;
    }
}

echo "ksa_products=" . count($ksaProducts) . "\n";
echo "uae_keys_with_images=" . count($uaeByKey) . "\n";
echo "matched_by_key=$matched\n";
echo "matched_with_file_on_disk=$matchedWithFile\n";
echo "matched_key_but_no_file=$matchedNoFile\n";
echo "no_uae_key=$missingKey\n";
if ($missingKeys) {
    echo "sample_unmatched:\n  " . implode("\n  ", $missingKeys) . "\n";
}
