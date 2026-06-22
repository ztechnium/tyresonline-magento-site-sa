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
namespace Ecomteck\OneStepCheckout\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\Layout;
use Magento\Store\Model\ScopeInterface;

class RemoveContainersPlugin
{
    const CONFIG_DISABLE_PATH = 'one_step_checkout/display/disable_%s';

    /**
     * Elements that can be disabled on the checkout page.
     *
     * @var array
     */
    const DISABLE_ELEMENTS = [
        'header'    => ['header.container', 'checkout.header.container'],
        'footer'    => ['footer-container'],
        'copyright' => ['copyright']
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;

    /**
     * @var Http
     */
    private $httpRequest;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $httpRequest
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $httpRequest,
        \Ecomteck\OneStepCheckout\Helper\Config $config
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->httpRequest = $httpRequest;
        $this->_config = $config;
    }

    /**
     * Preferably we would use a 'layout_render_before_checkout_index_index' event for this, but in 2.1, the
     * layout is rendered *before* this event is fired.
     *
     * See https://github.com/magento/magento2/pull/3907
     *
     * @param Layout $subject
     * @param array<int, mixed> $args
     * @return array
     */
    public function beforeRenderResult(Layout $subject, ...$args)
    {
        if (!$this->_config->isEnabled()) {
            return $args;
        }
        if ($this->httpRequest->getFullActionName() === 'checkout_index_index' || $this->httpRequest->getFullActionName() === 'onestepcheckout_index_index') {
            $layout = $subject->getLayout();
            foreach (self::DISABLE_ELEMENTS as $type => $elements) {
                $configPath = sprintf(self::CONFIG_DISABLE_PATH, $type);
                if ($this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE)) {
                    foreach ($elements as $element) {
                        $layout->unsetElement($element);
                    }
                }
            }
        }
        return $args;
    }
}
