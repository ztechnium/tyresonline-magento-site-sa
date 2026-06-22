<?php
use Magento\Framework\App\Bootstrap;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Setup\EavSetupFactory;

require '/var/www/magento/app/bootstrap.php';

$csvRelative = $argv[1] ?? '/var/www/magento/var/import/Master_sheet_tires_15_6_2026.csv';
$columns = ['mgs_brand', 'pattern', 'year', 'manufacturer', 'warranty_period', 'category_quality', 'discounts_types'];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

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
        $val = trim($row[$idx] ?? '');
        if ($val !== '') {
            $valuesByColumn[$col][$val] = true;
        }
    }
}
fclose($fh);

$eavConfig = $om->get(\Magento\Eav\Model\Config::class);
$optionManagement = $om->get(\Magento\Eav\Api\AttributeOptionManagementInterface::class);
$optionLabelFactory = $om->get(\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory::class);
$optionFactory = $om->get(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class);

foreach ($columns as $code) {
    if (empty($valuesByColumn[$code])) {
        continue;
    }
    try {
        /** @var AbstractAttribute $attribute */
        $attribute = $eavConfig->getAttribute('catalog_product', $code);
    } catch (\Throwable $e) {
        echo "Skip missing attribute $code\n";
        continue;
    }
    if (!$attribute || !$attribute->getId()) {
        echo "Skip missing attribute $code\n";
        continue;
    }
    if (!in_array($attribute->getFrontendInput(), ['select', 'multiselect'], true)) {
        echo "Skip non-select $code ({$attribute->getFrontendInput()})\n";
        continue;
    }

    $existing = [];
    foreach ($attribute->getSource()->getAllOptions(false) as $opt) {
        if (!empty($opt['value']) && !empty($opt['label'])) {
            $existing[strtolower(trim($opt['label']))] = $opt['label'];
        }
    }

    $added = 0;
    foreach (array_keys($valuesByColumn[$code]) as $label) {
        $key = strtolower(trim($label));
        if (isset($existing[$key])) {
            continue;
        }
        $option = $optionFactory->create();
        $option->setLabel($label);
        $option->setValue($label);
        $optionLabel = $optionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);
        $option->setStoreLabels([$optionLabel]);
        try {
            $optionManagement->add(
                \Magento\Catalog\Model\Product::ENTITY,
                $attribute->getAttributeId(),
                $option
            );
            $existing[$key] = $label;
            $added++;
        } catch (\Throwable $e) {
            echo "Failed to add $code option '$label': {$e->getMessage()}\n";
        }
    }
    echo "$code: added $added options\n";
}

echo "Done.\n";
