<?php

namespace Elgentos\ServerSideAnalytics\Observer;

use Elgentos\ServerSideAnalytics\Model\GAClient;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class SaveGaUserId implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;
    /**
     * @var GAClient
     */
    private $gaclient;

    /**
     * SaveGaUserId constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param GAClient $gaclient
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Psr\Log\LoggerInterface $logger,
                                \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
                                GAClient $gaclient)
    {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->cookieManager = $cookieManager;
        $this->gaclient = $gaclient;
    }

    /**
     * When Order object is saved add the GA User Id if available in the cookies.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */

    public function execute(Observer $observer)
    {
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ga-test.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('======JAIMIN=========');
        if (!$this->scopeConfig->getValue(GAClient::GOOGLE_ANALYTICS_SERVERSIDE_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }
		$logger->info('======JAIMIN 1 =========');
        if (!$this->scopeConfig->getValue(GAClient::GOOGLE_ANALYTICS_SERVERSIDE_UA, ScopeInterface::SCOPE_STORE)) {
            $this->logger->info('No Google Analytics account number has been found in the ServerSideAnalytics configuration.');
            return;
        }
		
		$logger->info('======JAIMIN 2=========');

        $gaUserId = $this->getUserIdFromCookie();
	
		
        if ($gaUserId === null) {
			$logger->info('======3=========');
            $gaCookieUserId = random_int(1E8, 1E9);
            $gaCookieTimestamp = time();
            $gaUserId = implode('.', [$gaCookieUserId, $gaCookieTimestamp]);
            $this->logger->info('Google Analytics cookie not found, generated temporary value: ' . $gaUserId);
        }

        $order = $observer->getEvent()->getOrder();

        $order->setData('ga_user_id', $gaUserId);
		$logger->info('======END=========');
    }

    /**
     * Try to get the Google Analytics User ID from the cookie
     *
     * @return string|null
     */
    protected function getUserIdFromCookie()
    {
        $gaCookie = explode('.', $this->cookieManager->getCookie('_ga') ?? '');
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/ga-test.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info('======START=========');

        if (empty($gaCookie) || count($gaCookie) < 4) {
            return null;
        }

        list(
            $gaCookieVersion,
            $gaCookieDomainComponents,
            $gaCookieUserId,
            $gaCookieTimestamp
            ) = $gaCookie;
		$logger->info('======1=========');
        if (!$gaCookieUserId || !$gaCookieTimestamp) {
            return null;
        }

        if ($gaCookieVersion != 'GA' . $this->gaclient->getVersion()) {
            $this->logger->info('Google Analytics cookie version differs from Measurement Protocol API version; please upgrade.');
            return null;
        }
		$logger->info('======2=========');
        return implode('.', [$gaCookieUserId, $gaCookieTimestamp]);
    }
}
