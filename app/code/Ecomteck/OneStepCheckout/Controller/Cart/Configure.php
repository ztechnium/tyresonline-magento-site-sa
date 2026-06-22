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

use Magento\Framework;
use Magento\Framework\Controller\ResultFactory;

class Configure extends \Magento\Checkout\Controller\Cart\Configure
{
    /**
     * Action to reconfigure cart item
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Extract item and product to configure
        $id = (int)$this->getRequest()->getParam('id');
        $quoteItem = null;
        if ($id) {
            $quoteItem = $this->cart->getQuote()->getItemById($id);
        }

        try {
            if (!$quoteItem) {
                $this->messageManager->addError(__("We can't find the quote item."));
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
            }
            $_GET['product_id'] = $quoteItem->getProduct()->getId();
            $this->getRequest()->setParam('product_id', $quoteItem->getProduct()->getId());

            $params = new \Magento\Framework\DataObject();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $params->setBuyRequest($quoteItem->getBuyRequest());

            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->_objectManager->get(\Magento\Catalog\Helper\Product\View::class)
                ->prepareAndRender(
                    $resultPage,
                    $quoteItem->getProduct()->getId(),
                    $this,
                    $params
                );
            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We cannot configure the product.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->_goBack();
        }
    }
}
