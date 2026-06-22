<?php
/**
 * Ecomteck
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Ecomteck.com license that is
 * available through the world-wide-web at this URL:
 * https://ecomteck.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Ecomteck
 * @package     Ecomteck_OneStepCheckout
 * @copyright   Copyright (c) 2018 Ecomteck (https://ecomteck.com/)
 * @license     https://ecomteck.com/LICENSE.txt
 */
namespace Ecomteck\OneStepCheckout\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    const CONFIG_PATH_HIDE_SHIPPING_METHODS   = 'one_step_checkout/shipping/hide_shipping_methods';
    const CONFIG_PATH_HIDE_SHIPPING_TITLE     = 'one_step_checkout/shipping/hide_shipping_title';
    const CONFIG_PATH_DEFAULT_PAYMENT_METHOD  = 'one_step_checkout/general/default_payment_method';
    const CONFIG_PATH_NEWSLETTER_ENABLED      = 'one_step_checkout/newsletter/enabled';
    const CONFIG_PATH_NEWSLETTER_CHECKED      = 'one_step_checkout/newsletter/checked';
    const CONFIG_PATH_NEWSLETTER_LABEL        = 'one_step_checkout/newsletter/label';

    const CONFIG_PATH_COMMENT_ENABLED         = 'one_step_checkout/comment/enabled';
    const CONFIG_PATH_COMMENT_PLACEHOLDER     = 'one_step_checkout/comment/placeholder';
    const CONFIG_PATH_ALWAYS_SHOW_CART_ITEMS  = 'one_step_checkout/display/always_show_cart_items';
    const CONFIG_PATH_DISABLE_COUPON_CODE     = 'one_step_checkout/display/disable_coupon_code';
    const CONFIG_PATH_COUPON_CODE_COLLAPSE_STATE     = 'one_step_checkout/display/coupon_code_collapse_state';
    const CONFIG_PATH_DISABLE_GIFT_OPTIONS    = 'one_step_checkout/display/disable_gift_options';
    const CONFIG_PATH_GIFT_OPTIONS_COLLAPSE_STATE     = 'one_step_checkout/display/gift_options_collapse_state';

    const CONFIG_PATH_ALLOW_UPDATE_QTY        = 'one_step_checkout/general/allow_update_product_qty';
    const CONFIG_PATH_ALLOW_REMOVE_PRODUCT    = 'one_step_checkout/general/allow_remove_product';
    const CONFIG_PATH_ALLOW_EDIT_PRODUCT_OPTION = 'one_step_checkout/general/allow_edit_product_option';
    const CONFIG_PATH_ADD_GIFT_MESSAGE_PER_ORDER = 'one_step_checkout/general/add_gift_message_per_order';
    const CONFIG_PATH_ADD_GIFT_MESSAGE_PER_ITEM = 'one_step_checkout/general/add_gift_message_per_item';
    const CONFIG_PATH_AUTO_SAVE_FILL_CUSTOMER_INFO = 'one_step_checkout/auto_complete/auto_save_fill_customer_info';
    const CONFIG_PATH_AUTO_COMPLETE_ENABLED   = 'one_step_checkout/auto_complete/enabled';
    const CONFIG_PATH_AUTO_COMPLETE_API_KEY   = 'one_step_checkout/auto_complete/api_key';
    const CONFIG_PATH_AUTO_COMPLETE_SPLIT     = 'one_step_checkout/auto_complete/split_street_fields';
    const CONFIG_PATH_MOVE_BILLING_ADDRESS_BEFORE_SHIPPING_ADDRESS =
        'one_step_checkout/display/move_billing_before_shipping';
    const CONFIG_PATH_UNCHECK_BILLING_IS_SAME_SHIPPING = 'one_step_checkout/display/uncheck_billing_is_same_shipping';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Ecomteck\OneStepCheckout\Helper\Config $config
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url,
        \Magento\Framework\App\Request\Http $request,
        \Ecomteck\OneStepCheckout\Helper\Config $config
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->request = $request;
        $this->_config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        if (!$this->_config->isEnabled()) {
            return [];
        }
    
        return [
            'onestepcheckout' => [
                'hideShippingMethods' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_HIDE_SHIPPING_METHODS,
                    ScopeInterface::SCOPE_STORE
                ),
                'hideShippingTitle' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_HIDE_SHIPPING_TITLE,
                    ScopeInterface::SCOPE_STORE
                ),
                'moveBillingAddressBeforeShippingAddressEnabled' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_MOVE_BILLING_ADDRESS_BEFORE_SHIPPING_ADDRESS,
                    ScopeInterface::SCOPE_STORE
                ),
                'uncheckBillingAddressIsSameShippingAddress' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_UNCHECK_BILLING_IS_SAME_SHIPPING,
                    ScopeInterface::SCOPE_STORE
                ),
                'newsletterEnabled' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_NEWSLETTER_ENABLED,
                    ScopeInterface::SCOPE_STORE
                ),
                'newsletterUrl' => $this->url->getUrl('onestepcheckout/newsletter/subscribe'),
                'newsletterChecked' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_NEWSLETTER_CHECKED,
                    ScopeInterface::SCOPE_STORE
                ),
                'newsletterLabel' => $this->scopeConfig->getValue(
                    self::CONFIG_PATH_NEWSLETTER_LABEL,
                    ScopeInterface::SCOPE_STORE
                ),
                'alwaysShowCartItems' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_ALWAYS_SHOW_CART_ITEMS,
                    ScopeInterface::SCOPE_STORE
                ),
                'allowUpdateQty' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_ALLOW_UPDATE_QTY,
                    ScopeInterface::SCOPE_STORE
                ),
                'allowRemoveProduct' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_ALLOW_REMOVE_PRODUCT,
                    ScopeInterface::SCOPE_STORE
                ),
                'allowEditProductOption' => (bool)$this->scopeConfig->getValue(
                    self::CONFIG_PATH_ALLOW_EDIT_PRODUCT_OPTION,
                    ScopeInterface::SCOPE_STORE
                ),
                'defaultPaymentMethod' => $this->scopeConfig->getValue(
                    self::CONFIG_PATH_DEFAULT_PAYMENT_METHOD,
                    ScopeInterface::SCOPE_STORE
                ),
                'giftMessage' => [
                    'perOrder' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_ADD_GIFT_MESSAGE_PER_ORDER,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'perItem' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_ADD_GIFT_MESSAGE_PER_ITEM,
                        ScopeInterface::SCOPE_STORE
                    )
                ],
                'display' => [
                    'disableCouponCode' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_DISABLE_COUPON_CODE,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'disableGiftOptions' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_DISABLE_GIFT_OPTIONS,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'couponCodeCollapseState' => (int)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_COUPON_CODE_COLLAPSE_STATE,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'giftOptionsCollapseState' => (int)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_GIFT_OPTIONS_COLLAPSE_STATE,
                        ScopeInterface::SCOPE_STORE
                    ),
                ],
                'autoComplete' => [
                    'enabled' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_AUTO_COMPLETE_ENABLED,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'autoSaveFillCustomerInfo' => (bool)$this->scopeConfig->getValue(
                        self::CONFIG_PATH_AUTO_SAVE_FILL_CUSTOMER_INFO,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'apiKey' => $this->scopeConfig->getValue(
                        self::CONFIG_PATH_AUTO_COMPLETE_API_KEY,
                        ScopeInterface::SCOPE_STORE
                    ),
                    'splitStreetFields' => $this->scopeConfig->getValue(
                        self::CONFIG_PATH_AUTO_COMPLETE_SPLIT,
                        ScopeInterface::SCOPE_STORE
                    ),
                ],
                'updateItemQtyUrl' => $this->url->getUrl(
                    'onestepcheckout/cart/updateItemQty',
                    ['_secure' => $this->request->isSecure()]
                ),
                'removeItemUrl' => $this->url->getUrl(
                    'onestepcheckout/cart/removeItem',
                    ['_secure' => $this->request->isSecure()]
                )
            ]
        ];
    }
}
