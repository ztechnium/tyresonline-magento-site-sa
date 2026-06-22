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

namespace Mageplaza\GoogleTagManager\Model\Config\Source;

/**
 * Class EventList
 * @package Mageplaza\GoogleTagManager\Model\Config\Source
 */
class EventList extends AbstractSource
{
    const SELECT           = 0;
    const SEARCH           = 1;
    const WISHLIST         = 2;
    const REGISTRATION     = 3;
    const LOGIN            = 4;
    const ORDER_REFUND     = 5;
    const PAYMENT          = 6;
    const SHIPPING         = 7;
    const ADD_TO_CART      = 8;
    const REMOVE_FROM_CART = 9;
    const BEGIN_CHECKOUT   = 10;
    const PURCHASE         = 11;
    const PRODUCT_PAGE     = 12;
    const CATEGORY_PAGE    = 13;
    const VIEW_CART        = 14;

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::SELECT           => __('-- Please select --'),
            self::SEARCH           => __('Search Result Page'),
            self::WISHLIST         => __('Add to Wishlist'),
            self::REGISTRATION     => __('Customer Registration'),
            self::LOGIN            => __('Customer Login After'),
            self::ORDER_REFUND     => __('Order Refunds'),
            self::PAYMENT          => __('Add Payment'),
            self::SHIPPING         => __('Add Shipping'),
            self::ADD_TO_CART      => __('Add to Cart'),
            self::REMOVE_FROM_CART => __('Remove from Cart'),
            self::BEGIN_CHECKOUT   => __('Begins Checkout'),
            self::PURCHASE         => __('Purchase'),
            self::PRODUCT_PAGE     => __('View Item'),
            self::CATEGORY_PAGE    => __('View Item List'),
            self::VIEW_CART        => __('View Cart')
        ];
    }
}