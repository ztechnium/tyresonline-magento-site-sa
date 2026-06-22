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
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\App\Response\Redirect;
use Magento\Store\Model\ScopeInterface;

class BackToStore extends Template
{
    const CONFIG_PATH_BACK_TO_STORE_LABEL = 'one_step_checkout/back_to_store/label';

    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @param Context $context
     * @param Redirect $redirect
     * @param array $data
     */
    public function __construct(Context $context, Redirect $redirect, array $data = [])
    {
        parent::__construct($context, $data);
        $this->redirect = $redirect;
    }

    /**
     * @return string
     */
    public function getBackToStoreLabel()
    {
        return $this->_scopeConfig->getValue(
            self::CONFIG_PATH_BACK_TO_STORE_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getBackToStoreUrl()
    {
        return $this->redirect->getRefererUrl();
    }
}
