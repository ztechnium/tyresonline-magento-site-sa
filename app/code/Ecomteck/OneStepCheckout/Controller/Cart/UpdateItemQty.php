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
namespace Ecomteck\OneStepCheckout\Controller\Cart;

class UpdateItemQty extends \Magento\Checkout\Controller\Sidebar\UpdateItemQty
{
    /**
     * Compile JSON response
     *
     * @param string $error
     * @return \Magento\Framework\App\Response\Http
     */
    protected function jsonResponse($error = '')
    {
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get(\Magento\Checkout\Model\Cart::class);
        $response = $this->sidebar->getResponseData($error);
        if ($cart->getItemsCount() == 0) {
            $response['redirect'] = true;
        }
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
