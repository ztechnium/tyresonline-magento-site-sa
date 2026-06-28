<?php
$file = '/var/www/magento/generated/metadata/primary|global|frontend|plugin-list.php';
$p = include $file;
foreach ([
    'Magento\\Framework\\Controller\\ResultInterface',
    'Magento\\Framework\\View\\Result\\Page',
] as $type) {
    echo "=== $type ===\n";
    if (isset($p[$type])) {
        foreach ($p[$type] as $name => $cfg) {
            if (stripos($name, 'cache') !== false || stripos($name, 'PageSpeed') !== false) {
                echo "  $name => {$cfg['instance']}\n";
            }
        }
    }
    $key = $type . '_afterRenderResult';
    echo 'afterRenderResult: ';
    echo isset($p[$key]) ? implode(', ', array_keys($p[$key])) : 'none';
    echo "\n\n";
}
