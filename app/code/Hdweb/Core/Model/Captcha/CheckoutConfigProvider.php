<?php
namespace Hdweb\Core\Model\Captcha;

class CheckoutConfigProvider extends \Magento\Captcha\Model\Checkout\ConfigProvider
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Captcha\Helper\Data $captchaData,
        array $formIds = ['user_login' => 'user_login']
    ) {
        parent::__construct($storeManager, $captchaData, $formIds);
    }
}
