<?php
/**
 * Download About Us hero images from UAE staging origin (same key paths as CMS).
 * Run on KSA server: cd /var/www/magento && sudo -u www-data php infra/scripts/fetch-about-us-images.php
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
Bootstrap::create(BP, $_SERVER);

$destDir = BP . '/pub/media/wysiwyg/about-us';
if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
    fwrite(STDERR, "Cannot create {$destDir}\n");
    exit(1);
}

$sources = [
    'https://stg.tyresonline.ae/media/wysiwyg/about-us/about-tyresonline-uae.jpg' => 'about-tyresonline-ksa.jpg',
    'https://stg.tyresonline.ae/media/wysiwyg/about-us/about-tyresonline-uae-mobile.jpg' => 'about-tyresonline-ksa-mobile.jpg',
    // Legacy filenames still referenced in older CMS copies
    'about-tyresonline-uae.jpg' => 'about-tyresonline-uae.jpg',
    'about-tyresonline-uae-mobile.jpg' => 'about-tyresonline-uae-mobile.jpg',
];

$fallbackPaths = [
    BP . '/pub/media/catalog/product/car-tyres/bridgestone_alenza_001.jpg',
    BP . '/pub/media/catalog/product/tyresonline-tyres/michelin_pilot_sport_4_1.jpg',
    'https://stg.tyresonline.ae/media/catalog/product/car-tyres/bridgestone_alenza_001.jpg',
];

function copyValidImage(string $source, string $dest): bool
{
    if (is_file($source)) {
        $data = file_get_contents($source);
    } else {
        $ctx = stream_context_create([
            'http' => ['timeout' => 30, 'header' => "User-Agent: TyresOnline-KSA-sync\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $data = @file_get_contents($source, false, $ctx);
    }
    if ($data === false || strlen($data) < 5000) {
        return false;
    }
    if (strncmp($data, "\xFF\xD8\xFF", 3) !== 0 && strncmp($data, "\x89PNG", 4) !== 0) {
        return false;
    }
    file_put_contents($dest, $data);
    echo "OK {$source} -> {$dest} (" . strlen($data) . " bytes)\n";
    return true;
}

$primary = [
    'https://stg.tyresonline.ae/media/wysiwyg/about-us/about-tyresonline-uae.jpg' => $destDir . '/about-tyresonline-ksa.jpg',
    'https://stg.tyresonline.ae/media/wysiwyg/about-us/about-tyresonline-uae-mobile.jpg' => $destDir . '/about-tyresonline-ksa-mobile.jpg',
];

$okDesktop = false;
$okMobile = false;
foreach ($primary as $url => $dest) {
    if (copyValidImage($url, $dest)) {
        if (str_contains($dest, 'ksa.jpg') && !str_contains($dest, 'mobile')) {
            $okDesktop = true;
        } else {
            $okMobile = true;
        }
    } else {
        echo "FAIL {$url}\n";
    }
}

if (!$okDesktop || !$okMobile) {
    echo "Primary URLs failed; trying UAE SSH rsync path via local catalog fallback...\n";
    foreach ($fallbackPaths as $path) {
        $tmp = $destDir . '/_fallback.jpg';
        if (copyValidImage($path, $tmp)) {
            if (!$okDesktop) {
                copy($tmp, $destDir . '/about-tyresonline-ksa.jpg');
                copy($tmp, $destDir . '/about-tyresonline-uae.jpg');
                $okDesktop = true;
                echo "Fallback desktop from {$path}\n";
            }
            if (!$okMobile) {
                copy($tmp, $destDir . '/about-tyresonline-ksa-mobile.jpg');
                copy($tmp, $destDir . '/about-tyresonline-uae-mobile.jpg');
                $okMobile = true;
                echo "Fallback mobile from {$path}\n";
            }
            unlink($tmp);
            break;
        }
    }
}

// Mirror ksa -> uae filenames for any stale CMS references
foreach (['about-tyresonline-ksa.jpg' => 'about-tyresonline-uae.jpg', 'about-tyresonline-ksa-mobile.jpg' => 'about-tyresonline-uae-mobile.jpg'] as $from => $to) {
    $src = $destDir . '/' . $from;
    $dst = $destDir . '/' . $to;
    if (is_file($src) && filesize($src) > 5000) {
        copy($src, $dst);
        echo "Mirrored {$from} -> {$to}\n";
    }
}

foreach (glob($destDir . '/*') as $f) {
    echo basename($f) . ' ' . filesize($f) . " bytes\n";
}

if (!$okDesktop) {
    fwrite(STDERR, "WARNING: desktop hero image still missing\n");
    exit(2);
}

echo "Done\n";
