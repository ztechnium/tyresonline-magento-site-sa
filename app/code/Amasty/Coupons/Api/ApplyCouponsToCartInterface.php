<?php

namespace Amasty\Coupons\Api;

use Amasty\Coupons\Api\Data\CouponApplyResultListInterface;

/**
 * Apply Coupons List to cart by cartId/quoteId.
 * @api
 */
interface ApplyCouponsToCartInterface
{
    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param int $cartId The cart ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     * @deprecared since 2.0.3, @see \Amasty\Coupons\Api\ApplyCouponsToCartInterface::applyToCart
     */
    public function apply(int $cartId, array $couponCodes);

    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons, and did quote items change
     *
     * @param int $cartId The cart ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultListInterface
     */
    public function applyToCart(int $cartId, array $couponCodes): CouponApplyResultListInterface;
}
