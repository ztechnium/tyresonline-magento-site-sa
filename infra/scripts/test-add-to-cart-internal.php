#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var \Magento\Framework\App\State $state */
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$pdo = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$pid = (int)$pdo->fetchOne("SELECT cpe.entity_id FROM catalog_product_entity cpe
  JOIN catalog_category_product cp ON cp.product_id=cpe.entity_id AND cp.category_id=1945
  JOIN cataloginventory_stock_status st ON st.product_id=cpe.entity_id AND st.stock_status=1 LIMIT 1");

echo "Testing product ID $pid\n";

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $repo */
$repo = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
/** @var \Magento\Checkout\Model\Cart $cart */
$cart = $om->get(\Magento\Checkout\Model\Cart::class);

try {
    $product = $repo->getById($pid, false, 1);
    echo "Product: {$product->getSku()}\n";
    echo "Salable: " . ($product->isSalable() ? 'yes' : 'no') . "\n";

    // Simulate Tyrefinder add path
    $cart->addProduct($product, ['qty' => 1]);
    $hasMobile = $om->create(\Hdweb\Installer\Helper\Data::class)->checkHasNoMobileVanTyreServiceProduct();
    echo "checkHasNoMobileVanTyreServiceProduct: $hasMobile\n";
    $quote = $cart->getQuote();
    $quote->setPickupDate('');
    $quote->save();
    $cart->save();

    $items = $cart->getQuote()->getAllItems();
    echo "Quote ID: " . $cart->getQuote()->getId() . "\n";
    echo "Items in quote: " . count($items) . "\n";
    foreach ($items as $item) {
        echo " - {$item->getSku()} qty={$item->getQty()}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
