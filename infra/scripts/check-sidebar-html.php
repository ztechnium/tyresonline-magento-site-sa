<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$params = $_SERVER;
$params[\Magento\Framework\App\Area::PARAM_AREA_CODE] = 'frontend';
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$url = 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html';
$html = @file_get_contents($url);
if (!$html) {
    echo "FETCH_FAILED\n";
    exit(1);
}
echo 'size=' . strlen($html) . PHP_EOL;
echo 'layered-filter-block=' . substr_count($html, 'layered-filter-block') . PHP_EOL;
echo 'filter-options=' . substr_count($html, 'filter-options') . PHP_EOL;
echo 'sidebar col-auto=' . substr_count($html, 'sidebar col-auto') . PHP_EOL;
echo 'Filter Options=' . substr_count($html, 'Filter Options') . PHP_EOL;
echo 'Search by Size=' . substr_count($html, 'Search by Size') . PHP_EOL;
