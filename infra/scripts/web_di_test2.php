<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$areaList = $om->get(Magento\Framework\App\AreaList::class);
$area = $areaList->getArea('frontend');
$area->load(Magento\Framework\App\Area::PART_CONFIG);
$area->load(Magento\Framework\App\Area::PART_TRANSLATION);
$area->load(Magento\Framework\App\Area::PART_DESIGN);

header('Content-Type: text/plain');
$tests = [
    'ItemPoolInterface' => Magento\Checkout\CustomerData\ItemPoolInterface::class,
    'DefaultConfigProvider' => Magento\Checkout\Model\DefaultConfigProvider::class,
    'CompositeConfigProvider' => Magento\Checkout\Model\CompositeConfigProvider::class,
];
foreach ($tests as $label => $class) {
    try {
        $obj = $om->get($class);
        echo "$label=OK " . get_class($obj) . PHP_EOL;
    } catch (Throwable $e) {
        echo "$label=FAIL: " . $e->getMessage() . PHP_EOL;
    }
}

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout');
$layout = $page->getLayout();
$layout->generateXml();
$layout->generateElements();
$html = $layout->renderNonCachedElement('checkout.root');
echo 'checkout_root_len=' . strlen($html) . PHP_EOL;
echo 'has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
