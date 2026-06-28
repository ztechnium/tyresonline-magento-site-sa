#!/usr/bin/env php
<?php
declare(strict_types=1);
/** Revert Magento media/static URLs to the origin hostname (use when CDN DNS is not ready). */

use Magento\Framework\App\Bootstrap;

$origin = 'https://stg.tyresonline.sa';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--origin=')) {
        $origin = rtrim(substr($arg, 9), '/');
    }
}

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$writer = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

$paths = [
    'web/unsecure/base_media_url' => $origin . '/media/',
    'web/secure/base_media_url' => $origin . '/media/',
    'web/unsecure/base_static_url' => $origin . '/static/',
    'web/secure/base_static_url' => $origin . '/static/',
];

echo "Reverting CDN URLs to {$origin}\n";
foreach ($paths as $path => $value) {
    echo "set {$path} = {$value}\n";
    $writer->save($path, $value, 'default', 0);
}

$om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->cleanType('config');
$om->get(\Magento\Framework\App\Cache\Manager::class)->flush(['config', 'full_page', 'block_html']);
echo "Cache flushed. Done.\n";
