<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;

require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$dir = $om->get(\Magento\Framework\Filesystem::class)->getDirectoryRead(DirectoryList::VAR_DIR);
$import = $om->create(Import::class);
$import->setData([
    'entity' => 'catalog_product',
    'behavior' => Import::BEHAVIOR_ADD_UPDATE,
    'validation_strategy' => 'validation-skip-errors',
    'allowed_error_count' => '10000',
    '_import_field_separator' => ',',
    '_import_multiple_value_separator' => ',',
]);
$src = new Csv($argv[1] ?? 'import/one.csv', $dir);
$import->validateSource($src);
foreach ($import->getErrorAggregator()->getAllErrors() as $e) {
    echo $e->getErrorMessage() . ' (row ' . $e->getRowNumber() . ")\n";
}
