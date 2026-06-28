<?php
declare(strict_types=1);
/**
 * Index all image files under pub/media/catalog/product for filename matching.
 */
$root = '/var/www/magento/pub/media/catalog/product';
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower($f->getExtension());
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) continue;
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root)));
    $rel = ltrim($rel, '/');
    $basename = strtolower(pathinfo($rel, PATHINFO_FILENAME));
    $files[$basename][] = '/' . $rel;
}
echo 'unique_basenames=' . count($files) . "\n";
echo 'total_files=' . array_sum(array_map('count', $files)) . "\n";
// sample
$i = 0;
foreach ($files as $bn => $paths) {
    if ($i++ >= 5) break;
    echo "$bn => {$paths[0]}\n";
}
