<?php
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');

$repo = $om->get(\Magento\Catalog\Model\ProductRepository::class);
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$productId = (int)$conn->fetchOne(
    "SELECT entity_id FROM catalog_product_entity_int cpei
     JOIN eav_attribute ea ON ea.attribute_id = cpei.attribute_id AND ea.attribute_code = 'warranty_period'
     WHERE cpei.value IS NOT NULL AND cpei.value != '' LIMIT 1"
);
if (!$productId) {
    $productId = (int)$conn->fetchOne('SELECT entity_id FROM catalog_product_entity LIMIT 1');
}
echo "Product ID: {$productId}\n";

foreach ([1 => 'EN', 2 => 'AR'] as $storeId => $label) {
    $em = $om->get(\Magento\Store\Model\App\Emulation::class);
    $em->startEnvironmentEmulation($storeId, 'frontend', true);
    try {
        $product = $repo->getById($productId, false, $storeId);
        $attrs = ['warranty_period', 'load_index', 'fuel_efficiency', 'speed_index'];
        echo "=== {$label} store {$storeId} ===\n";
        foreach ($attrs as $code) {
            $attr = $product->getResource()->getAttribute($code);
            $frontend = $attr ? (string)$attr->getFrontend()->getValue($product) : '';
            $helper = $om->get(\Hdweb\Tyrefinder\Helper\Productlisting::class)->getAttributeValue($product, $code);
            echo "{$code} frontend=[{$frontend}] helper=[{$helper}]\n";
        }
    } catch (\Throwable $e) {
        echo "{$label} error: {$e->getMessage()}\n";
    }
    $em->stopEnvironmentEmulation();
}
