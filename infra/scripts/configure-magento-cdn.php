#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Point Magento media/static URLs at the CDN hostname.
 * Usage: php configure-magento-cdn.php [--cdn=https://cdn.tyresonline.sa] [--dry-run]
 */

use Magento\Framework\App\Bootstrap;

$cdn = 'https://cdn.tyresonline.sa';
$dryRun = in_array('--dry-run', $argv, true);
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--cdn=')) {
        $cdn = rtrim(substr($arg, 6), '/');
    }
}

$mediaBase = $cdn . '/media/';
$staticBase = $cdn . '/static/';

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var \Magento\Framework\App\Config\Storage\WriterInterface $writer */
$writer = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

$paths = [
    'web/unsecure/base_media_url' => $mediaBase,
    'web/secure/base_media_url' => $mediaBase,
    'web/unsecure/base_static_url' => $staticBase,
    'web/secure/base_static_url' => $staticBase,
];

echo "CDN base: {$cdn}\n";
foreach ($paths as $path => $value) {
    echo ($dryRun ? '[dry-run] ' : '') . "set {$path} = {$value}\n";
    if (!$dryRun) {
        $writer->save($path, $value, 'default', 0);
    }
}

if (!$dryRun) {
    $om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->cleanType('config');
    $om->get(\Magento\Framework\App\Cache\Manager::class)->flush(['config', 'full_page', 'block_html']);
    echo "Cache flushed.\n";
}

echo "Done.\n";
