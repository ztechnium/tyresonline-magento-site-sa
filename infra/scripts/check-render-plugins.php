<?php
$file = '/var/www/magento/generated/metadata/primary|global|frontend|plugin-list.php';
$p = include $file;
foreach ($p as $key => $val) {
    if (strpos($key, 'renderResult') !== false || strpos($key, 'RenderResult') !== false) {
        echo "$key => " . (is_array($val) ? implode(', ', array_keys($val)) : json_encode($val)) . "\n";
    }
}
