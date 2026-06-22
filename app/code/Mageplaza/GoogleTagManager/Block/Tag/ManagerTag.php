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

namespace Mageplaza\GoogleTagManager\Block\Tag;

use DateTime;
use DateTimeZone;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Mageplaza\GoogleTagManager\Block\TagManager;
use Mageplaza\GoogleTagManager\Helper\Data;
use Magento\Framework\DataObject\IdentityInterface;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;

/**
 * Class ManagerTag
 * @package Mageplaza\GoogleTagManager\Block\Tag
 */
class ManagerTag extends TagManager implements IdentityInterface
{
    const RELATED   = 'related';
    const UPSELL    = 'up_sell';
    const CROSSSELL = 'cross_sell';
    const SEARCH    = 'search';

    /**
     * Get GTM Id
     *
     * @param $storeId
     *
     * @return array|mixed
     */
    public function getTagId($storeId = null)
    {
        return $this->_helper->getConfigGTM('tag_id', $storeId);
    }

    /**
     * Check condition show page
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canShowGtm()
    {
        return $this->_helper->isEnabled() && $this->_helper->getConfigGTM('enabled');
    }

    /**
     * Tag manager dataLayer
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getGtmDataLayer()
    {
        $action = $this->getFullNameAction();

        $trackPosition = explode(
            ',',
            $this->_helper->getConfigAnalytics4('track_position', $this->_helper->getStoreId()) ?: ''
        );

        switch ($action) {
            case 'cms_index_index':
                return $this->encodeJs($this->getHomeData());
            case 'catalogsearch_advanced_result':
            case 'catalogsearch_result_index':
                return $this->encodeJs($this->getSearchData());
            case 'catalog_category_view': // Product list page
                return $this->encodeJs($this->getCategoryData());
            case 'catalog_product_view': // Product detail view page
                return $this->encodeJs($this->getProductView($trackPosition));
            case 'checkout_index_index':  // Checkout page
                return $this->encodeJs($this->getCheckoutProductData('2', 'Checkout Page'));
            case 'checkout_cart_index':   // Shopping cart
                return $this->encodeJs($this->getCheckoutProductData('1', 'Shopping Cart'));
            case 'onestepcheckout_index_index': // Mageplaza One step check out page
                return $this->encodeJs($this->getCheckoutProductData('2', 'Checkout Page'));
            case 'checkout_onepage_success': // Purchase page
            case 'multishipping_checkout_success':
            case 'mpthankyoupage_index_index': // Mageplaza Thank you page
                return $this->encodeJs($this->getCheckoutSuccessData());
        }

        return $this->encodeJs($this->getDefaultData());
    }

    /**
     * @return string
     */
    public function getFullNameAction()
    {
        return $this->getRequest()->getFullActionName();
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getHomeData()
    {
        $data = [
            'ecommerce' => [
                'currencyCode' => $this->_helper->getCurrentCurrency()
            ]
        ];

        return $data;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getSearchData()
    {
        $data = [];

        $searchTerm = '';

        if ($this->getFullNameAction() == 'catalogsearch_advanced_result') {
            $searchCriterias = $this->getSearchCriterias();
            foreach (['left', 'right'] as $side) {
                if (!empty($searchCriterias[$side])) {
                    foreach ($searchCriterias[$side] as $criteria) {
                        $searchTerm .= $criteria['name'] . ': ' . $criteria['value'] . '; ';
                    }
                }
            }
        } elseif ($this->getFullNameAction() == 'catalogsearch_result_index') {
            $searchTerm = $this->queryFactory->get()->getQueryText();
        }

        $data['event']     = 'search';
        $data['ecommerce'] = [
            'searchterm' => $searchTerm
        ];

        if ($this->_helper->isEnabledGTMGa4() && in_array(Event::SEARCH, $this->_helper->getShowEvents())) {
            $data['ga4_event'] = 'search';
        }

        return $data;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCategoryData()
    {
        /** get current breadcrumb path name */
        $path          = $this->_helper->getBreadCrumbsPath();
        $products      = [];
        $productsGa4   = [];
        $result        = [];
        $itemsData     = [];
        $resultGa4     = [];
        $i             = 0;
        $categoryId    = $this->_registry->registry('current_category')->getId();
        $category      = $this->_category->load($categoryId);
        $storeId       = $category->getStore()->getId();
        $useIdOrSku    = $this->getUseIdOrSku($storeId);
        $loadedProduct = $this->getCategotyCollection($category);
        $this->_toolbar->setCollection($loadedProduct);

        $allItemsValue = 0;

        foreach ($loadedProduct as $item) {
            $i++;
            $allItemsValue            += $this->_helper->getPrice($item);
            $products[$i]['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
            $products[$i]['name']     = $item->getName();
            $products[$i]['price']    = $this->_helper->getPrice($item);
            $products[$i]['list']     = $category->getName();
            $products[$i]['position'] = $i;
            $products[$i]['category'] = $category->getName();

            if ($this->_helper->isEnabledBrand($item, $storeId)) {
                $products[$i]['brand'] = $this->_helper->getProductBrand($item);
            }

            if ($this->_helper->isEnabledVariant($item, $storeId)) {
                $products[$i]['variant'] = $this->_helper->getColor($item);
            }

            $products[$i]['path']          = implode(' > ', $path) . ' > ' . $item->getName();
            $products[$i]['category_path'] = implode(' > ', $path);
            $result[]                      = $products[$i];
            $itemsData[]                   = [
                'id'                       => $products[$i]['id'],
                'google_business_vertical' => 'retail'
            ];

            if ($this->_helper->isEnabledGTMGa4()
                && in_array(Event::CATEGORY_PAGE, $this->_helper->getShowEvents())) {
                $productsGa4[$i]['item_id']        = $useIdOrSku ? $item->getSku() : $item->getId();
                $productsGa4[$i]['item_name']      = $item->getName();
                $productsGa4[$i]['price']          = $this->_helper->getPrice($item);
                $productsGa4[$i]['item_list_name'] = $category->getName();
                $productsGa4[$i]['item_list_id']   = $category->getId();
                $productsGa4[$i]['index']          = $i;
                $productsGa4[$i]['quantity']       = $this->_helper->getQtySale($item);

                if ($this->_helper->isEnabledBrand($item, $storeId)) {
                    $productsGa4[$i]['item_brand'] = $this->_helper->getProductBrand($item);
                }

                if ($this->_helper->isEnabledVariant($item, $storeId)) {
                    $productsGa4[$i]['item_variant'] = $this->_helper->getColor($item);
                }

                if (!empty($path)) {
                    $j = null;
                    foreach ($path as $cat) {
                        $key                   = 'item_category' . $j;
                        $j                     = (int) $j;
                        $productsGa4[$i][$key] = $cat;
                        $j++;
                    }
                }

                $resultGa4[] = $productsGa4[$i];
            }
        }

        $data['event']             = 'view_item_list';
        $data['remarketing_event'] = 'view_item_list';
        $data['value']             = $allItemsValue;
        $data['items']             = $itemsData;

        $data['ecommerce'] = [
            'currency'    => $this->_helper->getCurrentCurrency(),
            'impressions' => $result
        ];

        if ($this->_helper->isEnabledGTMGa4()
            && in_array(Event::CATEGORY_PAGE, $this->_helper->getShowEvents())) {
            $data['ga4_event']          = 'view_item_list';
            $data['ecommerce']['items'] = $resultGa4;
        }

        return $data;
    }

    /**
     * Get GTM use ID or Sku
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUseIdOrSku($storeId = null)
    {
        return $this->_helper->getConfigGTM('use_id_or_sku', $storeId);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductView($trackPosition)
    {
        $data = [];

        $currentProduct = $this->_helper->getGtmRegistry()->registry('product');

        $data['curent_product'] = $this->_helper->getProductDetailData($currentProduct, $trackPosition);

        $related = $this->_helper->getRelatedProductData($currentProduct);
        $upSell  = $this->_helper->getUpSellProductData($currentProduct);

        if (count($related) && in_array(self::RELATED, $trackPosition) && $this->_helper->isEnabledGTMGa4()) {
            $data['related'] = [
                'event'     => 'view_item_list',
                'ecommerce' => [
                    'item_list_id'   => 'related_products',
                    'item_list_name' => 'Related products',
                    'currency'       => $this->_helper->getCurrentCurrency(),
                    'items'          => $related
                ],
                'ga4_event' => 'view_item_list'
            ];
        }
        if (count($upSell) && in_array(self::UPSELL, $trackPosition) && $this->_helper->isEnabledGTMGa4()) {
            $data['up_sell'] = [
                'event'     => 'view_item_list',
                'ecommerce' => [
                    'item_list_id'   => 'upsell_products',
                    'item_list_name' => 'Up-sell products',
                    'currency'       => $this->_helper->getCurrentCurrency(),
                    'items'          => $upSell
                ],
                'ga4_event' => 'view_item_list'
            ];
        }

        return $data;
    }

    /**
     * Get product data in checkout page
     *
     * @param string $step
     * @param string $option
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getCheckoutProductData($step, $option = 'Checkout')
    {
        $cart = $this->_cart;
        // retrieve quote items array
        $items       = $cart->getQuote()->getAllVisibleItems();
        $products    = [];
        $productsGa4 = [];
        $i           = 1;
        $values      = 0;

        if (empty($items)) {
            return [];
        }

        foreach ($items as $item) {
            $products[] = $this->_helper->getProductCheckOutData($item);
            $values     += $item->getPrice() * $item->getQty();
            if ($this->_helper->isEnabledGTMGa4()) {
                $productGa4          = $this->_helper->getProductGa4CheckOutData($item);
                $productGa4['index'] = $i;
                $productsGa4[]       = $productGa4;
                $i++;
            }
        }

        $eCommProdId = [];
        foreach ($products as $product) {
            $eCommProdId[] = $product['id'];
        }

        if ($step === '1') {
            $data = [
                'event'     => 'view_cart',
                'currency'  => $this->_helper->getCurrentCurrency(),
                'value'     => $values,
                'ecommerce' => [
                    'currency' => $this->_helper->getCurrentCurrency(),
                    'checkout' => [
                        'actionField' => [
                            'step'   => $step,
                            'option' => $option
                        ],
                        'products'    => $products
                    ]
                ]
            ];

            if ($this->_helper->isEnabledGTMGa4() && in_array(Event::VIEW_CART, $this->_helper->getShowEvents())) {
                $data['ga4_event']                  = 'view_cart';
                $data['ecommerce']['itemsviewcart'] = $productsGa4;
            }
        }

        if ($step === '2') {
            $data = [
                'event'     => 'begin_checkout',
                'currency'  => $this->_helper->getCurrentCurrency(),
                'value'     => $values,
                'ecommerce' => [
                    'currency' => $this->_helper->getCurrentCurrency(),
                    'checkout' => [
                        'actionField' => [
                            'step'   => $step,
                            'option' => $option
                        ],
                        'products'    => $products
                    ]
                ]
            ];

            if ($this->_helper->isEnabledGTMGa4() && in_array(Event::BEGIN_CHECKOUT, $this->_helper->getShowEvents())) {
                $data['ga4_event']          = 'begin_checkout';
                $data['ecommerce']['items'] = $productsGa4;
            }
        }

        return $data;
    }

    /**
     * @return array|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputException
     */
    protected function getCheckoutSuccessData()
    {
        $order = $this->_helper->getSessionManager()->getLastRealOrder();

        if ($this->isMultiShipping()) {
            $orderIds       = $this->getMultiShipping()->getOrderIds();
            $baseGrandTotal = 0;
            $data           = [];
            if ($orderIds) {
                foreach ($orderIds as $orderId) {
                    /** @var Order $or */
                    $or                          = $this->orderRepository->get($orderId);
                    $baseGrandTotal              += $this->_helper->calculateTotals($or);
                    $data[$or->getIncrementId()] = $this->getCheckoutSuccessDataEachOrder($or);
                    if ($or->getShippingMethod() != null) {
                        $data['shipping'] = $this->getShippingDataEachOrder($or);
                    }

                    if ($or->getPayment()->getMethodInstance()->getCode() != 'free') {
                        $data['payment'] = $this->getPaymentDataEachOrder($or);
                    }

                    $data['purchase']             = $this->getCheckoutSuccessDataEachOrder($or);
                    $data['purchase']['enhanced'] = $this->getEnhancedConversionData($or);
                }
            }

            if ($this->_helper->isEnabledIgnoreOrders($this->_helper->getStoreId()) && $baseGrandTotal <= 0) {
                return [];
            }

            return $data;
        }

        if ($this->_helper->isEnabledIgnoreOrders($this->_helper->getStoreId())
            && $this->_helper->calculateTotals($order) <= 0) {
            return [];
        }

        if($order->getCustomerId() != null
            && $this->_helper->getSessionManager()->getGTMLoginData()){
            $data['register'] = $this->_helper->getGTMSignUpData();
        }

        if ($order->getShippingMethod() != null) {
            $data['shipping'] = $this->getShippingDataEachOrder($order);
        }

        if ($order->getPayment()->getMethodInstance()->getCode() != 'free') {
            $data['payment'] = $this->getPaymentDataEachOrder($order);
        }

        $data['purchase']             = $this->getCheckoutSuccessDataEachOrder($order);
        $data['purchase']['enhanced'] = $this->getEnhancedConversionData($order);

        return $data;
    }

    /**
     * @param Order $order
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCheckoutSuccessDataEachOrder($order)
    {
        $items = $order->getItemsCollection([], true);

        $products    = [];
        $productsGa4 = [];
        $skuItems    = [];
        $skuItemsQty = [];

        /** @var Item $item */
        foreach ($items as $item) {
            $productSku    = $item->getProduct()->getSku();
            $products[]    = $this->_helper->getProductOrderedData($item);
            $skuItems[]    = $productSku;
            $skuItemsQty[] = $productSku . ':' . (int) $item->getQtyOrdered();
            if ($this->_helper->isEnabledGTMGa4()) {
                $productsGa4[] = $this->_helper->getGa4ProductOrderedData($item);
            }
        }

        $itemsData = [];
        foreach ($products as $product) {
            $itemsData[] = [
                'id'                       => $product['id'],
                'google_business_vertical' => 'retail'
            ];
        }

        $data['remarketing_event'] = 'purchase';
        $data['value']             = $this->_helper->calculateTotals($order);
        $data['items']             = $itemsData;

        $createdAt = $this->timezone->date(
            new DateTime($order->getCreatedAt(), new DateTimeZone('UTC')),
            $this->localeResolver->getLocale(),
            true
        );

        $data['ecommerce'] = [
            'purchase' => [
                'actionField' => [
                    'id'          => $order->getIncrementId(),
                    'affiliation' => $this->_helper->getAffiliationName(),
                    'order_id'    => $order->getIncrementId(),
                    'subtotal'    => $order->getSubtotal(),
                    'shipping'    => $order->getShippingAmount(),
                    'tax'         => $order->getTaxAmount(),
                    'revenue'     => $this->_helper->calculateTotals($order),
                    'discount'    => $order->getDiscountAmount(),
                    'coupon'      => (string) $order->getCouponCode(),
                    'created_at'  => $createdAt->format('Y-m-d H:i:s'),
                    'items'       => implode(';', $skuItems),
                    'items_qty'   => implode(';', $skuItemsQty)
                ],
                'products'    => $products
            ],
            'currency' => $this->_helper->getCurrentCurrency()
        ];

        if ($this->_helper->isEnabledGTMGa4() && in_array(Event::PURCHASE, $this->_helper->getShowEvents())) {
            $data['ga4_event']                   = 'purchase';
            $data['ecommerce']['transaction_id'] = $order->getIncrementId();
            $data['ecommerce']['affiliation']    = $this->_helper->getAffiliationName();
            $data['ecommerce']['value']          = $this->_helper->calculateTotals($order);
            $data['ecommerce']['tax']            = $order->getTaxAmount();
            $data['ecommerce']['shipping']       = $order->getShippingAmount();
            $data['ecommerce']['currency']       = $this->_helper->getCurrentCurrency();
            $data['ecommerce']['coupon']         = (string) $order->getCouponCode();
            $data['ecommerce']['items']          = $productsGa4;
        }

        return $data;
    }

    /**
     * @param $order
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    protected function getShippingDataEachOrder($order)
    {
        $items = $order->getItemsCollection([], true);

        $products    = [];
        $productsGa4 = [];
        $skuItems    = [];
        $skuItemsQty = [];

        /** @var Item $item */
        foreach ($items as $item) {
            $productSku    = $item->getProduct()->getSku();
            $products[]    = $this->_helper->getProductOrderedData($item);
            $skuItems[]    = $productSku;
            $skuItemsQty[] = $productSku . ':' . (int) $item->getQtyOrdered();
            if ($this->_helper->isEnabledGTMGa4()) {
                $productsGa4[] = $this->_helper->getGa4ProductOrderedData($item);
            }
        }

        $itemsData = [];
        foreach ($products as $product) {
            $itemsData[] = [
                'id'                       => $product['id'],
                'google_business_vertical' => 'retail'
            ];
        }

        $data['remarketing_event'] = 'add_shipping_info';
        $data['value']             = $this->_helper->calculateTotals($order);
        $data['shipping_tier']     = $order->getShippingMethod();
        $data['items']             = $itemsData;

        $createdAt = $this->timezone->date(
            new DateTime($order->getCreatedAt(), new DateTimeZone('UTC')),
            $this->localeResolver->getLocale(),
            true
        );

        $data['ecommerce'] = [
            'add_shipping_info' => [
                'actionField' => [
                    'id'          => $order->getIncrementId(),
                    'affiliation' => $this->_helper->getAffiliationName(),
                    'order_id'    => $order->getIncrementId(),
                    'subtotal'    => $order->getSubtotal(),
                    'shipping'    => $order->getShippingAmount(),
                    'tax'         => $order->getTaxAmount(),
                    'revenue'     => $this->_helper->calculateTotals($order),
                    'discount'    => $order->getDiscountAmount(),
                    'coupon'      => (string) $order->getCouponCode(),
                    'created_at'  => $createdAt->format('Y-m-d H:i:s'),
                    'items'       => implode(';', $skuItems),
                    'items_qty'   => implode(';', $skuItemsQty)
                ],
                'products'    => $products
            ],
            'currency'          => $this->_helper->getCurrentCurrency()
        ];

        if ($this->_helper->isEnabledGTMGa4() && in_array(Event::SHIPPING, $this->_helper->getShowEvents())) {
            $data['ga4_event']                   = 'add_shipping_info';
            $data['ecommerce']['transaction_id'] = $order->getIncrementId();
            $data['ecommerce']['affiliation']    = $this->_helper->getAffiliationName();
            $data['ecommerce']['value']          = $this->_helper->calculateTotals($order);
            $data['ecommerce']['tax']            = $order->getTaxAmount();
            $data['ecommerce']['shipping']       = $order->getShippingAmount();
            $data['ecommerce']['currency']       = $this->_helper->getCurrentCurrency();
            $data['ecommerce']['coupon']         = (string) $order->getCouponCode();
            $data['ecommerce']['items']          = $productsGa4;
        }

        return $data;
    }

    /**
     * @param $order
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    protected function getPaymentDataEachOrder($order)
    {
        $items = $order->getItemsCollection([], true);

        $products    = [];
        $productsGa4 = [];
        $skuItems    = [];
        $skuItemsQty = [];

        /** @var Item $item */
        foreach ($items as $item) {
            $productSku    = $item->getProduct()->getSku();
            $products[]    = $this->_helper->getProductOrderedData($item);
            $skuItems[]    = $productSku;
            $skuItemsQty[] = $productSku . ':' . (int) $item->getQtyOrdered();
            if ($this->_helper->isEnabledGTMGa4()) {
                $productsGa4[] = $this->_helper->getGa4ProductOrderedData($item);
            }
        }

        $itemsData = [];
        foreach ($products as $product) {
            $itemsData[] = [
                'id'                       => $product['id'],
                'google_business_vertical' => 'retail'
            ];
        }

        $data['remarketing_event'] = 'add_payment_info';
        $data['value']             = $this->_helper->calculateTotals($order);
        $data['payment_type']      = $order->getPayment()->getMethodInstance()->getTitle();
        $data['items']             = $itemsData;

        $createdAt = $this->timezone->date(
            new DateTime($order->getCreatedAt(), new DateTimeZone('UTC')),
            $this->localeResolver->getLocale(),
            true
        );

        $data['ecommerce'] = [
            'add_shipping_info' => [
                'actionField' => [
                    'id'          => $order->getIncrementId(),
                    'affiliation' => $this->_helper->getAffiliationName(),
                    'order_id'    => $order->getIncrementId(),
                    'subtotal'    => $order->getSubtotal(),
                    'shipping'    => $order->getShippingAmount(),
                    'tax'         => $order->getTaxAmount(),
                    'revenue'     => $this->_helper->calculateTotals($order),
                    'discount'    => $order->getDiscountAmount(),
                    'coupon'      => (string) $order->getCouponCode(),
                    'created_at'  => $createdAt->format('Y-m-d H:i:s'),
                    'items'       => implode(';', $skuItems),
                    'items_qty'   => implode(';', $skuItemsQty)
                ],
                'products'    => $products
            ],
            'currency'          => $this->_helper->getCurrentCurrency()
        ];

        if ($this->_helper->isEnabledGTMGa4() && in_array(Event::PAYMENT, $this->_helper->getShowEvents())) {
            $data['ga4_event']                   = 'add_payment_info';
            $data['ecommerce']['transaction_id'] = $order->getIncrementId();
            $data['ecommerce']['affiliation']    = $this->_helper->getAffiliationName();
            $data['ecommerce']['value']          = $this->_helper->calculateTotals($order);
            $data['ecommerce']['tax']            = $order->getTaxAmount();
            $data['ecommerce']['shipping']       = $order->getShippingAmount();
            $data['ecommerce']['currency']       = $this->_helper->getCurrentCurrency();
            $data['ecommerce']['coupon']         = (string) $order->getCouponCode();
            $data['ecommerce']['items']          = $productsGa4;
        }

        return $data;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getDefaultData()
    {
        $data = [
            'ecommerce' => [
                'currencyCode' => $this->_helper->getCurrentCurrency()
            ]
        ];

        return $data;
    }

    /**
     * Get enhanced conversion tracking data
     *
     * @param $order
     *
     * @return array
     */
    public function getEnhancedConversionData($order)
    {
        $data = [];
        /** @var Order $order */
        $addess = $order->getShippingAddress() ?: $order->getBillingAddress();

        $data['email']       = $addess->getEmail() ?: '';
        $data['first_name']  = $addess->getFirstname() ?: '';
        $data['last_name']   = $addess->getLastname() ?: '';
        $data['phone']       = $addess->getTelephone() ?: '';
        $data['street']      = implode(', ', $addess->getStreet()) ?: '';
        $data['city']        = $addess->getCity() ?: '';
        $data['region']      = $addess->getRegion() ?: '';
        $data['postal_code'] = $addess->getPostcode() ?: '';
        $data['country']     = $addess->getCountryId() ?: '';

        return $data;
    }

    /**
     * @return array
     */
    public function getDimensionsAndMetrics()
    {
        $values                 = [];
        $customMap['page_path'] = $this->_helper->getCurrentUrl();
        $customDimensions       = $this->_helper->getDimensions();
        $customMetrics          = $this->_helper->getMetrics();
        $dimensionsOptions      = $customDimensions ? Data::jsonDecode($customDimensions) : [];
        $metricsOptions         = $customMetrics ? Data::jsonDecode($customMetrics) : [];

        [$customMap, $values] = $this->getDimensionsMetricsInformation($dimensionsOptions, $customMap, $values);
        [$customMap, $values] = $this->getDimensionsMetricsInformation($metricsOptions, $customMap, $values, false);

        return [$customMap, $values];
    }

    /**
     * @param array $Options
     * @param array $customMap
     * @param array $values
     * @param $isDimensions
     *
     * @return array
     */
    protected function getDimensionsMetricsInformation(
        array $Options,
        array $customMap,
        array $values,
        $isDimensions = true
    ) {
        if (!isset($customMap['custom_map'])) {
            $customMap['custom_map'] = [];
        }
        if (isset($Options['option']['value']) && $Options['option']['value']) {
            foreach ($Options['option']['value'] as $option) {
                $key = 'metric' . $option['index'];
                if ($isDimensions) {
                    $key = $option['index'];
                }
                if ($this->_helper->getValueDimensionsMetrics($option['value'])) {
                    $k                             = $isDimensions
                        ? $option['name'] : $option['name'];
                    $customMap['custom_map'][$key] = $k;
                    $values[$k]                    = $this->_helper->getValueDimensionsMetrics($option['value']);
                }
            }
        }

        return [$customMap, $values];
    }

    /**
     * @return string
     */
    public function getGa4TagId()
    {
        [$measurementId, $secretAPI] = $this->_helper->getGa4TagIdAndSecretAPI();

        return $measurementId;
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return ['mp_gtm_analytics4'];
    }
}
