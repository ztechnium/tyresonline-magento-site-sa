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

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Framework\App\RequestInterface;
use Magento\CatalogRule\Model\ResourceModel\Product\CollectionProcessor as CatalogRuleProcessor;
use Magento\Framework\App\ObjectManager;

/**
 * Class AddToWishlist
 * @package Mageplaza\GoogleTagManager\Observer
 */
class AddToWishlist implements ObserverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CatalogRuleProcessor
     */
    protected $catalogRuleProcessor;

    /**
     * @param HelperData $helper
     * @param ProductFactory $productFactory
     * @param RequestInterface $request
     * @param CatalogRuleProcessor|null $catalogRuleProcessor
     */
    public function __construct(
        HelperData $helper,
        ProductFactory $productFactory,
        RequestInterface $request,
        ?CatalogRuleProcessor $catalogRuleProcessor = null
    ) {
        $this->helper               = $helper;
        $this->productFactory       = $productFactory;
        $this->request              = $request;
        $this->catalogRuleProcessor = $catalogRuleProcessor ?? ObjectManager::getInstance()
            ->get(CatalogRuleProcessor::class);
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function execute(Observer $observer)
    {
        $canShowEvents = $this->helper->getShowEvents();

        if ($this->helper->isEnabled() && in_array(Event::WISHLIST, $canShowEvents)) {
            $items = $observer->getData('items');
            foreach ($items as $item) {
                $product  = $item->getProduct();
                $quantity = $item->getData('qty');

                switch ($product->getTypeId()){
                    case 'configurable':
                        $selectedProduct = $this->productFactory->create();
                        $selectedProduct->load($selectedProduct->getIdBySku($product->getSku()));
                        $this->setGTMAddToWishlistData($selectedProduct, $quantity);
                        $this->setFBAddToWishlist($selectedProduct, $quantity);

                        break;
                    case 'grouped':
                        $groupQuantities   = $this->request->getParam('super_group');
                        $groupProductPrice = 0;
                        if ($groupQuantities != null) {
                            $groupProducts = $product->getTypeInstance()->getAssociatedProducts($product);
                            foreach ($groupProducts as $itemProduct) {
                                foreach ($groupQuantities as $keyGroupQuantity => $itemGroupQuantity) {
                                    if ($keyGroupQuantity == $itemProduct->getId()) {
                                        $groupProductPrice += $itemProduct->getPrice() * $itemGroupQuantity;
                                    }
                                }
                            }
                            $this->setGTMAddToWishlistData($product, $quantity, $groupProductPrice);
                            $this->setFBAddToWishlist($product, $quantity, $groupProductPrice);
                        } else {
                            $this->setGTMAddToWishlistData($product, $quantity, $groupProductPrice);
                            $this->setFBAddToWishlist($product, $quantity, $groupProductPrice);
                        }

                        break;
                    case 'bundle':
                        $bundleQuantities   = $this->request->getParam('bundle_option_qty');
                        $bundleProductPrice = 0;
                        $typeInstance       = $product->getTypeInstance();
                        $typeInstance->setStoreFilter($product->getStoreId(), $product);

                        $optionCollection    = $typeInstance->getOptionsCollection($product);
                        $selectionCollection = $typeInstance->getSelectionsCollection(
                            $typeInstance->getOptionsIds($product),
                            $product
                        );
                        $this->catalogRuleProcessor->addPriceData($selectionCollection);
                        $selectionCollection->addTierPriceData();

                        $options = $optionCollection->appendSelections(
                            $selectionCollection
                        );

                        foreach ($options as $option) {
                            if ($bundleQuantities == null) {
                                $bundleProductPrice += $option->getSelections()[0]->getPrice();
                            } else {
                                foreach ($option->getSelections() as $selection) {
                                    foreach ($bundleQuantities as $keyBundleQuantity => $itemBundleQuantity) {
                                        if ($keyBundleQuantity == $selection->getOptionId()) {
                                            $bundleProductPrice += $selection->getPrice() * $itemBundleQuantity;
                                        }
                                    }
                                }
                            }

                        }
                        $this->setGTMAddToWishlistData($product, $quantity, $bundleProductPrice);
                        $this->setFBAddToWishlist($product, $quantity, $bundleProductPrice);
                        break;
                    default:
                        $this->setGTMAddToWishlistData($product, $quantity);
                        $this->setFBAddToWishlist($product, $quantity);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * @param $product
     * @param $qty
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function setGTMAddToWishlistData($product, $qty, $price = null)
    {
        if ($this->helper->getConfigGTM('enabled')) {
            $this->helper->getSessionManager()->setGTMAddToWishlistData($this->helper->getGTMAddToWishListData($product,
                $qty, $price));
        }
    }

    /**
     * @param $product
     * @param $quantity
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setFBAddToWishlist($product, $quantity, $price = null)
    {
        $productData = $this->helper->getFBAddToWishlistData($product, $quantity);
        if ($this->helper->getSessionManager()->getPixelAddToWishlistData()) {
            $data   = $this->helper->getSessionManager()->getPixelAddToWishlistData();
            $status = true;
            foreach ($data['contents'] as $key => $value) {
                if ($product->getId() === $value['id']) {
                    $status                             = false;
                    $data['contents'][$key]['quantity'] += $quantity;
                }
            }
            if ($status) {
                $data['content_ids'][]  = $productData['id'];
                $data['content_name'][] = $productData['name'];
                $data['value']          += (float) $productData['price'] * $quantity;
                $data['contents'][]     = $productData;
            }
        } else {
            $data = [
                'content_ids'  => [$productData['id']],
                'content_name' => [$productData['name']],
                'content_type' => 'product',
                'contents'     => [$productData],
                'currency'     => $this->helper->getCurrentCurrency(),
                'value'        => (float) $productData['price'] * $quantity
            ];
        }
        if (isset($data['value']) && $price != null) {
            $data['value'] = $price;
        }
        $this->helper->getSessionManager()->setPixelAddToWishlistData($data);
    }
}
