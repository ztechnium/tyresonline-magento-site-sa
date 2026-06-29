#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'en';
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();
$om->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Hditsol/tyresonline', 'frontend');

$cart = $om->get(\Magento\Checkout\Model\Cart::class);
$productRepository = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$cart->truncate()->save();
$product = $productRepository->getById(4715, false, null, true);
$cart->addProduct($product, ['qty' => 4]);
$cart->save();

$request = $om->get(\Magento\Framework\App\Request\Http::class);
$request->setRouteName('checkout')->setControllerName('cart')->setActionName('index');
$view = $om->get(\Magento\Framework\App\View::class);
$view->loadLayout(['checkout_cart_index'], true, true);
$layout = $view->getLayout();

function dumpStructure($layout, $parentName, $name, $depth = 0): void
{
    $element = $layout->getBlock($name);
    if ($element) {
        echo str_repeat('  ', $depth) . "block:$name (" . get_class($element) . ")\n";
    } else {
        $children = $layout->getChildNames($name);
        if ($children) {
            echo str_repeat('  ', $depth) . "container:$name children=[" . implode(',', $children) . "]\n";
        } else {
            echo str_repeat('  ', $depth) . "MISSING:$name\n";
            return;
        }
    }
    foreach ($layout->getChildNames($name) as $child) {
        dumpStructure($layout, $name, $child, $depth + 1);
    }
}

echo "=== checkout.cart structure ===\n";
foreach ($layout->getChildNames('checkout.cart') as $child) {
    dumpStructure($layout, 'checkout.cart', $child, 1);
}

echo "\n=== cart.bottom.main structure ===\n";
foreach ($layout->getChildNames('cart.bottom.main') ?: [] as $child) {
    dumpStructure($layout, 'cart.bottom.main', $child, 1);
}

echo "\n=== cart.bottom.right structure ===\n";
foreach ($layout->getChildNames('cart.bottom.right') ?: [] as $child) {
    dumpStructure($layout, 'cart.bottom.right', $child, 1);
}

$view->renderLayout();
$html = $layout->getOutput();
$pos = strpos($html, 'discount-cart-summary');
echo "\npage_has_discount_cart_summary=" . ($pos !== false ? 'yes at ' . $pos : 'no') . "\n";
echo 'checkoutConfig=' . substr_count($html, 'checkoutConfig') . "\n";
