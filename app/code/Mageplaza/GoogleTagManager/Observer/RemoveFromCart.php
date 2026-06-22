<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class RemoveFromCart
 * @package Mageplaza\GoogleTagManager\Observer
 */
class RemoveFromCart implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollection;

    /**
     * RemoveFromCart constructor.
     *
     * @param ProductFactory $productFactory
     * @param Data $helper
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        ProductFactory $productFactory,
        Data $helper,
        CollectionFactory $categoryCollection
    ) {
        $this->_productFactory     = $productFactory;
        $this->_helper             = $helper;
        $this->_categoryCollection = $categoryCollection;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->isEnabled()) {
            $quoteItem = $observer->getData('quote_item');
            $qty       = $quoteItem->getQty();

            if ($quoteItem->getProductType() === 'configurable') {
                $selectedProduct = $this->_productFactory->create();
                $selectedProduct->load($selectedProduct->getIdBySku($quoteItem->getSku()));
                $this->setGARemoveFromCartData($selectedProduct, $qty);
                $this->sendRemoveFromCartToGa4($selectedProduct, $qty);
            } else {
                $this->setGARemoveFromCartData($quoteItem, $qty);
                $this->sendRemoveFromCartToGa4($quoteItem, $qty);
            }
        }

        return $this;
    }

    /**
     * @param Product $product
     * @param float $qty
     *
     * @throws NoSuchEntityException
     */
    protected function setGARemoveFromCartData($product, $qty)
    {
        if ($this->_helper->getConfigAnalytics('enabled')) {
            $this->_helper->getSessionManager()->setGARemoveFromCartData($this->_helper->getGARemoveFromCartData(
                $product,
                $qty
            ));
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function sendRemoveFromCartToGa4($product, $qty)
    {
        if ($this->_helper->isEnabledGTMGa4()
            && in_array(Event::REMOVE_FROM_CART, $this->_helper->getShowEvents())) {
            [$measurementId, $secretAPI] = $this->_helper->getGa4TagIdAndSecretAPI();
            $i = 0;

            foreach ($measurementId as $id) {
                if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                    continue;
                }

                $sessionId = $this->_helper->getSessionId($measurementId[$i]);
                $payload   = json_encode($this->getBodyData($product, $sessionId, $qty));

                $url = $this->_helper->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                $this->_helper->measureProtocolGA4($url, $payload);

                $i++;

                if ($i == count($measurementId) - 1) {
                    break;
                }
            }
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getBodyData($product, $sessionId, $qty)
    {
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                      = $this->_helper->getClientId();
        $resultGa4["events"]["name"]                 = "remove_from_cart";
        $resultGa4["events"]["params"]["currency"]   = $this->_helper->getCurrentCurrency();
        $resultGa4["events"]["params"]["session_id"] = $sessionId;

        $categoryIds = $product->getCategoryIds();
        $categories  = $this->_categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id',
            $categoryIds);

        $itemsArray[$i]["item_id"]     = $product->getSku();
        $itemsArray[$i]["item_name"]   = $product->getName();
        $itemsArray[$i]["affiliation"] = $this->_helper->getAffiliationName();
        $itemsArray[$i]["currency"]    = $this->_helper->getCurrentCurrency();
        $itemsArray[$i]["price"]       = abs($this->_helper->convertPrice($product->getPrice()));
        $itemsArray[$i]["quantity"]    = abs($qty);

        if (!empty($categories)) {
            $j = 0;
            foreach ($categories as $cat) {
                $key                  = "item_category" . $j;
                $itemsArray[$i][$key] = $cat->getName();
                $j++;
            }
        }

        $productGa4[] = $itemsArray[$i];

        $resultGa4["events"]["params"]["value"]      = $this->_helper->convertPrice($product->getPrice());
        $resultGa4["events"]["params"]["debug_mode"] = 1;
        $resultGa4["events"]["params"]["items"]      = $productGa4;

        return $resultGa4;
    }
}
