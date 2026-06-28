<?php
$data = include '/var/www/magento/generated/metadata/primary|global|frontend|plugin-list.php';
$list = $data[0] ?? $data;
$type = 'Magento\\Framework\\Controller\\ResultInterface';
echo "Plugins on $type:\n";
foreach ($list[$type] ?? [] as $name => $cfg) {
    echo "  $name (order {$cfg['sortOrder']}) => {$cfg['instance']}\n";
}
// Check second array for method chains
if (isset($data[1])) {
    foreach ($data[1] as $key => $chain) {
        if (strpos($key, 'ResultInterface') !== false && strpos($key, 'renderResult') !== false) {
            echo "Chain $key: " . json_encode($chain) . "\n";
        }
    }
}
// dump keys containing renderResult
foreach ($data as $idx => $section) {
    if (!is_array($section)) continue;
    foreach ($section as $key => $val) {
        if (stripos($key, 'renderResult') !== false) {
            echo "Section $idx key $key: " . json_encode($val) . "\n";
        }
    }
}
