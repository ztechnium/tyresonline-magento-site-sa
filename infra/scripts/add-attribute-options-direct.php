<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';

$csvRelative = $argv[1] ?? '/var/www/magento/var/import/Master_sheet_tires_15_6_2026.csv';
$columns = ['mgs_brand', 'pattern', 'year', 'manufacturer', 'warranty_period', 'tyre_size'];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$connection = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$eavConfig = $om->get(\Magento\Eav\Model\Config::class);

$valuesByColumn = array_fill_keys($columns, []);
$fh = fopen($csvRelative, 'r');
$header = fgetcsv($fh);
$indexes = [];
foreach ($columns as $col) {
    $idx = array_search($col, $header, true);
    if ($idx !== false) {
        $indexes[$col] = $idx;
    }
}
while (($row = fgetcsv($fh)) !== false) {
    foreach ($indexes as $col => $idx) {
        $val = trim((string)($row[$idx] ?? ''));
        if ($val !== '') {
            $valuesByColumn[$col][$val] = true;
        }
    }
}
fclose($fh);

function addSelectOptions($connection, $eavConfig, string $code, array $labels): int
{
    $attribute = $eavConfig->getAttribute('catalog_product', $code);
    if (!$attribute || !$attribute->getId()) {
        echo "Missing attribute $code\n";
        return 0;
    }
    if (!in_array($attribute->getFrontendInput(), ['select', 'multiselect'], true)) {
        echo "Skip non-select $code\n";
        return 0;
    }

    $attributeId = (int)$attribute->getAttributeId();
    $existing = [];
    foreach ($attribute->getSource()->getAllOptions(false) as $opt) {
        if (!empty($opt['label'])) {
            $existing[strtolower(trim((string)$opt['label']))] = true;
        }
    }

    $added = 0;
    foreach ($labels as $label) {
        $label = trim((string)$label);
        if ($label === '' || isset($existing[strtolower($label)])) {
            continue;
        }
        $connection->insert('eav_attribute_option', [
            'attribute_id' => $attributeId,
            'sort_order' => 0,
        ]);
        $optionId = (int)$connection->lastInsertId('eav_attribute_option');
        $connection->insert('eav_attribute_option_value', [
            'option_id' => $optionId,
            'store_id' => 0,
            'value' => $label,
        ]);
        $existing[strtolower($label)] = true;
        $added++;
        echo "Added $code option: $label (id $optionId)\n";
    }
    return $added;
}

foreach ($columns as $code) {
    $count = addSelectOptions($connection, $eavConfig, $code, array_keys($valuesByColumn[$code]));
    echo "$code total added: $count\n";
}

echo "Done.\n";
