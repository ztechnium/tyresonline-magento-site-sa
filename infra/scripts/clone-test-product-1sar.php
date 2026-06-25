#!/usr/bin/env php
<?php
/**
 * Clone an existing simple product and set price to 1 SAR for staging tests.
 *
 * Usage:
 *   php infra/scripts/clone-test-product-1sar.php [--source-sku=SKU] [--sku=NEW-SKU]
 */
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var State $state */
$state = $om->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$options = getopt('', ['source-sku:', 'sku:', 'name:']);
$newSku = $options['sku'] ?? 'TEST-TYRE-1-SAR';
$newName = $options['name'] ?? 'Test Tyre 1 SAR';
$sourceSku = $options['source-sku'] ?? null;

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $om->get(ProductRepositoryInterface::class);
/** @var ProductFactory $productFactory */
$productFactory = $om->get(ProductFactory::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $om->get(StoreManagerInterface::class);

if (!$sourceSku) {
    $resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
    $conn = $resource->getConnection();
    $sourceSku = (string)$conn->fetchOne(
        "SELECT sku FROM catalog_product_entity WHERE type_id = 'simple' ORDER BY entity_id DESC LIMIT 1"
    );
}

if ($sourceSku === '') {
    fwrite(STDERR, "No source product found.\n");
    exit(1);
}

try {
    $existing = $productRepository->get($newSku);
    echo "Product already exists:\n";
    echo "  ID:  {$existing->getId()}\n";
    echo "  SKU: {$existing->getSku()}\n";
    echo "  Price: {$existing->getPrice()}\n";
    echo "  URL: {$existing->getProductUrl()}\n";
    exit(0);
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    // expected
}

$source = $productRepository->get($sourceSku, true, $storeManager->getStore()->getId(), true);
echo "Cloning source SKU: {$sourceSku} (ID {$source->getId()})\n";

$duplicate = $productFactory->create();
$duplicateData = $source->getData();
unset(
    $duplicateData['entity_id'],
    $duplicateData['row_id'],
    $duplicateData['created_at'],
    $duplicateData['updated_at'],
    $duplicateData['url_path'],
    $duplicateData['category_ids'],
    $duplicateData['media_gallery']
);
$duplicate->setData($duplicateData);
$duplicate->setId(null);
$duplicate->setMediaGalleryEntries([]);
$duplicate->setSku($newSku);
$duplicate->setUrlKey(strtolower(str_replace([' ', '_'], '-', $newSku)));
$duplicate->setName($newName);
$duplicate->setPrice(1);
$duplicate->setStatus(Status::STATUS_ENABLED);
$duplicate->setVisibility(Visibility::VISIBILITY_BOTH);
$duplicate->setCategoryIds($source->getCategoryIds());
$duplicate->setWebsiteIds($source->getWebsiteIds());
$duplicate->setStockData([
    'use_config_manage_stock' => 0,
    'manage_stock' => 1,
    'is_in_stock' => 1,
    'qty' => 999,
]);

$saved = $productRepository->save($duplicate);

echo "Created test product:\n";
echo "  ID:  {$saved->getId()}\n";
echo "  SKU: {$saved->getSku()}\n";
echo "  Price: {$saved->getPrice()}\n";
echo "  URL: {$saved->getProductUrl()}\n";
