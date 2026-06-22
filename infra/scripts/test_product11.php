<?php
use Magento\Framework\App\Bootstrap;
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$om->get(\Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('en');

$core = $om->get(\Hdweb\Core\Helper\Data::class);
$listing = $om->get(\Hdweb\Tyrefinder\Helper\Productlisting::class);
$image = $om->get(\Magento\Catalog\Helper\Image::class);

$product = $om->get(\Magento\Catalog\Model\ProductFactory::class)->create()->load(6943);
echo "Product: {$product->getSku()}\n";

$tests = [
    'formatSpeedRating' => fn() => $core->formatSpeedRating('V'),
    'image init' => fn() => $image->init($product, 'category_page_grid')->getUrl(),
    'display_name attr' => fn() => $product->getResource()->getAttribute('display_name')->getFrontend()->getValue($product),
    'isAnyRuleExist' => fn() => $listing->isAnyRuleExist($product),
    'getTyreSize' => fn() => $listing->getTyreSize($product, true),
    'media gallery' => fn() => $product->getMediaGalleryImages()->getSize(),
];

foreach ($tests as $name => $fn) {
    try {
        $r = $fn();
        echo "$name: OK ($r)\n";
    } catch (\Throwable $e) {
        echo "$name: FAIL - {$e->getMessage()}\n";
    }
}
