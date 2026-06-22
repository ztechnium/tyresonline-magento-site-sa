<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;

require '/var/www/magento/app/bootstrap.php';

$csvRelative = $argv[1] ?? 'import/Master_sheet_tires_15_6_2026.csv';
$behavior = $argv[2] ?? Import::BEHAVIOR_ADD_UPDATE;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$varDir = $om->get(\Magento\Framework\Filesystem::class)->getDirectoryRead(DirectoryList::VAR_DIR);
if (!$varDir->isExist($csvRelative)) {
    fwrite(STDERR, "CSV not readable: var/$csvRelative\n");
    exit(1);
}

/** @var Import $import */
$import = $om->create(Import::class);
$history = $om->get(\Magento\ImportExport\Model\History::class);
$import->setImportHistoryModel($history);

$import->setData([
    'entity' => 'catalog_product',
    'behavior' => $behavior,
    'validation_strategy' => 'validation-skip-errors',
    'allowed_error_count' => '10000',
    '_import_field_separator' => ',',
    '_import_multiple_value_separator' => ',',
    '_import_empty_attribute_value_constant' => '__EMPTY__VALUE__',
]);

$source = new Csv($csvRelative, $varDir);
$import->setSource($source);

echo "Validating and staging bunches...\n";
$import->validateSource($source);
$aggregator = $import->getErrorAggregator();
echo 'Invalid rows: ' . $aggregator->getInvalidRowsCount() . "\n";
echo 'Error count: ' . $aggregator->getErrorsCount() . "\n";

$connection = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$bunches = (int)$connection->fetchOne('SELECT COUNT(*) FROM importexport_importdata');
echo "Bunches staged: $bunches\n";

echo "Importing staged data...\n";
try {
    $import->importSource();
} catch (Throwable $e) {
    echo 'Import exception: ' . $e->getMessage() . "\n";
    exit(1);
}

$import->invalidateIndex();
echo 'Summary: ' . $history->getSummary() . "\n";
echo 'Rows: ' . $import->getProcessedRowsCount() . ', entities: ' . $import->getProcessedEntitiesCount() . "\n";
foreach ($aggregator->getAllErrors() as $error) {
    echo 'ERR row ' . $error->getRowNumber() . ': ' . $error->getErrorMessage() . "\n";
}

$products = (int)$connection->fetchOne('SELECT COUNT(*) FROM catalog_product_entity');
echo "Product count now: $products\n";
