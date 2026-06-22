<?php

namespace Meetanshi\WorldpayHp\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Meetanshi\WorldpayHp\Helper\Data;

/**
 * Class WorldpayHpConfigProvider
 * @package Meetanshi\WorldpayHp\Model
 */
class WorldpayHpConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var array
     */
    protected $methodCodes = ['worldpay_hp'];
    /**
     * @var array
     */
    protected $methods = [];

    /**
     * WorldpayHpConfigProvider constructor.
     * @param Data $helper
     * @param PaymentHelper $paymentHelper
     * @param StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(Data $helper, PaymentHelper $paymentHelper, StoreManagerInterface $storeManager)
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $redirectUrl = $this->storeManager->getStore()->getBaseUrl() . 'worldpay_hp/payment/redirect';
        $showLogo = $this->helper->showLogo();
        $imageUrl = $this->helper->getPaymentLogo();

        $config = [];
        $config['payment']['worldpay_hp_payment']['imageurl'] = ($showLogo) ? $imageUrl : '';
        $config['payment']['worldpay_hp_payment']['is_active'] = $this->helper->isActive();
        $config['payment']['worldpay_hp_payment']['payment_instruction'] = trim($this->helper->getPaymentInstructions());
        $config['payment']['worldpay_hp_payment']['redirect_url'] = $redirectUrl;

        return $config;
    }
}
