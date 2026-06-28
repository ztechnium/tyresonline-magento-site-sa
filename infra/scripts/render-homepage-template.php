<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';

$params = $_SERVER;
$params['MAGE_MODE'] = 'developer';
$bootstrap = Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();

$state = $om->get('Magento\Framework\App\State');
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
$storeManager->setCurrentStore(2);

$layout = $om->create('Magento\Framework\View\Layout');
$block = $layout->createBlock('Magento\Framework\View\Element\Template');
$block->setTemplate('Magento_Theme::home-page.phtml');

$html = $block->toHtml();
if (preg_match('/data-src="([^"]+)"/', $html, $m, PREG_OFFSET_CAPTURE)) {
    // find map section
}
if (preg_match('/map-image[\s\S]{0,500}/', $html, $m)) {
    echo "Rendered map section:\n" . $m[0] . "\n";
}
if (strpos($html, 'ksa-map') !== false) echo "HAS ksa-map\n";
if (strpos($html, 'Map.svg') !== false) echo "HAS Map.svg\n";
if (strpos($html, 'partnership networks') !== false) echo "HAS new partnership text\n";
if (strpos($html, '7 Emirates') !== false) echo "HAS 7 Emirates text\n";

$resolver = $om->get('Magento\Framework\View\Element\Template\File\Resolver');
$file = $resolver->getTemplateFileName('Magento_Theme::home-page.phtml');
echo "Resolved template file: $file\n";
