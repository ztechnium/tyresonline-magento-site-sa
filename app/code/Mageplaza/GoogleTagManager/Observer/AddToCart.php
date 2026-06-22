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
use Magento\Framework\ObjectManagerInterface;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class AddToCart
 * @package Mageplaza\GoogleTagManager\Observer
 */
class AddToCart implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollection;

    /**
     * AddToCart constructor.
     *
     * @param ProductFactory $productFactory
     * @param ObjectManagerInterface $objectManager
     * @param Data $helper
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        ProductFactory $productFactory,
        ObjectManagerInterface $objectManager,
        Data $helper,
        CollectionFactory $categoryCollection
    ) {
        $this->_productFactory = $productFactory;
        $this->_objectManager  = $objectManager;
        $this->_helper         = $helper;
        $this->_categoryCollection = $categoryCollection;
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->_helper->isEnabled()) {
            $product = $observer->getData('product');
            $request = $observer->getData('request');

            $qty = $request->getParam('qty') ?: 1;
            if ($product->getTypeId() === 'configurable') {
                $selectedProduct = $this->_productFactory->create();
                $selectedProduct->load($selectedProduct->getIdBySku($product->getSku()));
                $this->setPixelAddToCartData($selectedProduct, $qty);
                $this->setGAAddToCartData($selectedProduct, $qty);
                $this->sendAddToCartDataToGa4($selectedProduct, $qty);
            } else {
                $this->setPixelAddToCartData($product, $qty);
                $this->setGAAddToCartData($product, $qty);
                $this->sendAddToCartDataToGa4($product, $qty);
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
    protected function setPixelAddToCartData($product, $qty)
    {
        if ($this->_helper->getConfigPixel('enabled')) {
            $products = $this->_helper->getFBAddToCartData($product, $qty);
            if ($this->_helper->getSessionManager()->getPixelAddToCartData()) {
                $data   = $this->_helper->getSessionManager()->getPixelAddToCartData();
                $status = true;
                foreach ($data['contents'] as $key => $value) {
                    if ($product->getId() === $value['id']) {
                        $status                             = false;
                        $data['contents'][$key]['quantity'] += $qty;
                    }
                }
                if ($status) {
                    $data['content_ids'][]  = $products['id'];
                    $data['content_name'][] = $products['name'];
                    $data['value']          += (float) $products['price'];
                    $data['contents'][]     = $products;
                }
            } else {
                $data = [
                    'content_ids'  => [$products['id']],
                    'content_name' => [$products['name']],
                    'content_type' => 'product',
                    'contents'     => [$products],
                    'currency'     => $this->_helper->getCurrentCurrency(),
                    'value'        => (float) $products['price']
                ];
            }
            $this->_helper->getSessionManager()->setPixelAddToCartData($data);
        }
    }

    /**
     * @param Product $product
     * @param float $qty
     *
     * @throws NoSuchEntityException
     */
    protected function setGAAddToCartData($product, $qty)
    {
        if ($this->_helper->getConfigAnalytics('enabled')) {
            $products = $this->_helper->getGAAddToCartData($product, $qty);
            if ($this->_helper->getSessionManager()->getGAAddToCartData()) {
                $data   = $this->_helper->getSessionManager()->getGAAddToCartData();
                $status = true;
                foreach ($data['items'] as $key => $value) {
                    if ($product->getId() === $value['id']) {
                        $status                          = false;
                        $data['items'][$key]['quantity'] += $qty;
                    }
                }
                if ($status) {
                    $data['items'][] = $products;
                }
            } else {
                $data = [
                    'items' => [$products],
                ];
            }
            $this->_helper->getSessionManager()->setGAAddToCartData($data);
        }
    }

    /**
     * @param Product $product
     * @param float $qty
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function sendAddToCartDataToGa4($product, $qty){
        if($this->_helper->isEnabledGTMGa4()
        && in_array(Event::ADD_TO_CART, $this->_helper->getShowEvents())){
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
     * @param $product
     * @param $sessionId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getBodyData($product, $sessionId, $qty){
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                      = $this->_helper->getClientId();
        $resultGa4["events"]["name"]                 = "add_to_cart";
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

        $resultGa4["events"]["params"]["value"]      = abs($this->_helper->convertPrice($product->getPrice()));
        $resultGa4["events"]["params"]["debug_mode"] = 1;
        $resultGa4["events"]["params"]["items"]      = $productGa4;

        return $resultGa4;
    }
}
