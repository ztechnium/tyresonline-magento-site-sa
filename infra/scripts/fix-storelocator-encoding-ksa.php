#!/usr/bin/env php
<?php
/**
 * Permanent fix: ensure Arabic storelocator template comes from DB (correct UTF-8),
 * not a stale/corrupted config.php override or config cache.
 *
 * Usage: php infra/scripts/fix-storelocator-encoding-ksa.php [--remove-config-override]
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$configPath = 'ecomteck_storelocator/template/location_list_description';
$removeOverride = in_array('--remove-config-override', $argv ?? [], true);

function templateHasMojibake(string $template): bool
{
    return (bool)preg_match('/[\x{2500}-\x{25FF}]/u', $template);
}

function extractMapSpan(string $template): string
{
    if (preg_match('/google-map-direction.*?<span>([^<]+)<\\/span>/us', $template, $m)) {
        return trim($m[1]);
    }
    return '';
}

$correctTemplate = (string)$conn->fetchOne(
    'SELECT value FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
    [$configPath, 'stores', 2]
);

if ($correctTemplate === '' || !preg_match('/[\x{0600}-\x{06FF}]/u', $correctTemplate)) {
    fwrite(STDERR, "Arabic DB template missing or invalid; run fix-storelocator-template-ar-ksa.php first.\n");
    exit(1);
}

echo 'DB map span: ' . extractMapSpan($correctTemplate) . "\n";

$configFile = BP . '/app/etc/config.php';
$configChanged = false;

if (is_readable($configFile)) {
    $config = include $configFile;
    $paths = [
        ['system', 'stores', 'ar', 'ecomteck_storelocator', 'template', 'location_list_description'],
        ['system', 'stores', 2, 'ecomteck_storelocator', 'template', 'location_list_description'],
    ];

    foreach ($paths as $path) {
        $ref = &$config;
        $exists = true;
        foreach ($path as $key) {
            if (!is_array($ref) || !array_key_exists($key, $ref)) {
                $exists = false;
                break;
            }
            $ref = &$ref[$key];
        }
        unset($ref);

        if (!$exists) {
            continue;
        }

        // Navigate again to read/remove
        $ref = &$config;
        foreach ($path as $i => $key) {
            if ($i === count($path) - 1) {
                $current = (string)$ref[$key];
                $span = extractMapSpan($current);
                echo 'config.php override span: ' . $span . "\n";

                if ($removeOverride || templateHasMojibake($current) || $current !== $correctTemplate) {
                    unset($ref[$key]);
                    $configChanged = true;
                    echo 'Removed config.php override at ' . implode('/', array_slice($path, 0, -1)) . "\n";
                }
                break;
            }
            $ref = &$ref[$key];
        }
        unset($ref);
    }

    if ($configChanged) {
        $php = "<?php\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($configFile, $php) === false) {
            fwrite(STDERR, "Failed to write config.php\n");
            exit(1);
        }
        echo "Updated config.php\n";
    } else {
        echo "config.php override OK or absent\n";
    }
}

// Ensure DB row is clean (fix AE URLs)
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
    echo "Fixed AE->SA URLs in DB template\n";
    $correctTemplate = $fixedTemplate;
}

// Flush config caches via shell (cache manager needs area)
passthru('php ' . escapeshellarg(BP . '/bin/magento') . ' cache:clean config compiled_config 2>&1', $code);
passthru('php ' . escapeshellarg(BP . '/bin/magento') . ' cache:flush 2>&1', $code2);

$scopeConfig = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore(2);
$live = (string)$scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 2);
$span = extractMapSpan($live);

echo "After fix scopeConfig map span: {$span}\n";
if (templateHasMojibake($live)) {
    fwrite(STDERR, "ERROR: scopeConfig still has mojibake.\n");
    exit(1);
}

echo "Done.\n";
