<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$quoteId = 29;
$quote = $om->get(Magento\Quote\Api\CartRepositoryInterface::class)->get($quoteId);
$session = $om->get(Magento\Checkout\Model\Session::class);
$session->replaceQuote($quote);
$session->setQuoteId($quoteId);

echo 'items=' . (int)$quote->getItemsCount() . PHP_EOL;
echo 'pickup_date=' . ($quote->getPickupDate() ?: 'EMPTY') . PHP_EOL;
echo 'pickup_time=' . ($quote->getPickupTime() ?: 'EMPTY') . PHP_EOL;
echo 'pickup_store=' . ($quote->getPickupStore() ?: 'EMPTY') . PHP_EOL;

$checkoutHelper = $om->get(Magento\Checkout\Helper\Data::class);
echo 'canOnepage=' . ($checkoutHelper->canOnepageCheckout() ? 'yes' : 'no') . PHP_EOL;
echo 'guest_ok=' . ($checkoutHelper->isAllowedGuestCheckout($quote) ? 'yes' : 'no') . PHP_EOL;
echo 'min_amount=' . ($quote->validateMinimumAmount() ? 'yes' : 'no') . PHP_EOL;

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
    echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
    if ($html) {
        echo substr($html, 0, 500) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
