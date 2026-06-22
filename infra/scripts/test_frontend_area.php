<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$state = $om->get(Magento\Framework\App\State::class);
$state->emulateAreaCode('frontend', function () use ($om) {
    $tests = [
        'ItemPoolInterface' => Magento\Checkout\CustomerData\ItemPoolInterface::class,
        'CaptchaConfigProvider' => Magento\Captcha\Model\Checkout\ConfigProvider::class,
        'CompositeConfigProvider' => Magento\Checkout\Model\CompositeConfigProvider::class,
        'Onepage' => Magento\Checkout\Block\Onepage::class,
    ];

    foreach ($tests as $label => $class) {
        try {
            $obj = $om->get($class);
            echo "$label=OK " . get_class($obj) . PHP_EOL;
        } catch (Throwable $e) {
            echo "$label=FAIL " . $e->getMessage() . PHP_EOL;
        }
    }

    $pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
    $page = $pageFactory->create(false, ['cacheable' => false]);
    $page->addHandle('checkout_index_index');
    $page->addHandle('onestepcheckout');
    $layout = $page->getLayout();
    $layout->generateXml();
    $layout->generateElements();
    try {
        $html = $layout->renderNonCachedElement('checkout.root');
        echo 'checkout_root_len=' . strlen($html) . PHP_EOL;
        echo 'has_config=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (Throwable $e) {
        echo 'checkout_root_ERR=' . $e->getMessage() . PHP_EOL;
    }
});
