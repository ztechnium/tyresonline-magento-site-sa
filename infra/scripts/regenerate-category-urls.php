<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');

$categoryRepository = $om->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);

$categoryIds = range(1944, 1976);
$stores = [0, 1, 2];

foreach ($stores as $storeId) {
    $storeManager->setCurrentStore($storeId);
    echo "Store $storeId\n";
    foreach ($categoryIds as $id) {
        try {
            $category = $categoryRepository->get($id, $storeId ?: null);
            if ($storeId === 0) {
                $category->setStoreId(0);
            }
            $categoryRepository->save($category);
            echo "  saved category $id (url_key={$category->getUrlKey()})\n";
        } catch (Throwable $e) {
            echo "  skip $id: {$e->getMessage()}\n";
        }
    }
}

echo "Done.\n";
