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
namespace Ecomteck\OneStepCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Footer extends Template
{
    const CONFIG_PATH_FOOTER_CONTENT = 'one_step_checkout/display/footer_content';

    /**
     * Gets footer content from store config.
     *
     * @return string
     */
    public function getFooterContent()
    {
        return $this->_scopeConfig->getValue(
            self::CONFIG_PATH_FOOTER_CONTENT,
            ScopeInterface::SCOPE_STORE
        );
    }
}
