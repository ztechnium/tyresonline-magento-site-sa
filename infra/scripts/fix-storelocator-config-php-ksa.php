#!/usr/bin/env php
<?php
/**
 * Fix storelocator template encoding: sync correct UTF-8 Arabic from DB into app/etc/config.php
 * and purge config cache so scopeConfig serves proper Arabic (not mojibake).
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$configPath = 'ecomteck_storelocator/template/location_list_description';

$correctTemplate = (string)$conn->fetchOne(
    'SELECT value FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
    [$configPath, 'stores', 2]
);

if ($correctTemplate === '') {
    fwrite(STDERR, "No Arabic store template found in DB.\n");
    exit(1);
}

if (!preg_match('/[\x{0600}-\x{06FF}]/u', $correctTemplate)) {
    fwrite(STDERR, "DB template does not contain Arabic; aborting.\n");
    exit(1);
}

echo 'DB template length: ' . strlen($correctTemplate) . "\n";

$configFile = BP . '/app/etc/config.php';
if (!is_readable($configFile)) {
    fwrite(STDERR, "config.php not found.\n");
    exit(1);
}

$config = include $configFile;
$updated = false;

if (isset($config['system']['stores']['ar']['ecomteck_storelocator']['template']['location_list_description'])) {
    $current = $config['system']['stores']['ar']['ecomteck_storelocator']['template']['location_list_description'];
    if ($current !== $correctTemplate) {
        $config['system']['stores']['ar']['ecomteck_storelocator']['template']['location_list_description'] = $correctTemplate;
        $updated = true;
        echo "Updated config.php stores/ar template\n";
    }
}

// Some exports nest under numeric store id
if (isset($config['system']['stores'][2]['ecomteck_storelocator']['template']['location_list_description'])) {
    $current = $config['system']['stores'][2]['ecomteck_storelocator']['template']['location_list_description'];
    if ($current !== $correctTemplate) {
        $config['system']['stores'][2]['ecomteck_storelocator']['template']['location_list_description'] = $correctTemplate;
        $updated = true;
        echo "Updated config.php stores/2 template\n";
    }
}

if (!$updated) {
    echo "config.php already has correct template or no store override found.\n";
} else {
    $export = var_export($config, true);
    $php = "<?php\nreturn " . $export . ";\n";
    if (file_put_contents($configFile, $php) === false) {
        fwrite(STDERR, "Failed to write config.php\n");
        exit(1);
    }
    echo "Wrote config.php\n";
}

// Also rewrite DB to ensure consistency
$conn->update(
    'core_config_data',
    ['value' => $correctTemplate],
    ['path = ?' => $configPath, 'scope = ?' => 'stores', 'scope_id = ?' => 2]
);

// Fix SA URLs in template while we're at it
$fixedTemplate = str_replace(
    ['https://stg.tyresonline.ae/storepickup/index/selectstore', 'https://stg.tyresonline.ae/static/'],
    ['https://stg.tyresonline.sa/storepickup/index/selectstore', 'https://stg.tyresonline.sa/static/'],
    $correctTemplate
);
if ($fixedTemplate !== $correctTemplate) {
    $conn->update(
        'core_config_data',
        ['value' => $fixedTemplate],
        ['path = ?' => $configPath, 'scope = ?' => 'stores', 'scope_id = ?' => 2]
    );
    if (isset($config['system']['stores']['ar']['ecomteck_storelocator']['template']['location_list_description'])) {
        $config['system']['stores']['ar']['ecomteck_storelocator']['template']['location_list_description'] = $fixedTemplate;
        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");
    }
    echo "Fixed AE->SA URLs in template\n";
    $correctTemplate = $fixedTemplate;
}

echo "Done. Flush cache: bin/magento cache:flush\n";
