<?php

namespace Amasty\Coupons\Api;

use Amasty\Coupons\Api\Data\CouponApplyResultListInterface;

/**
 * Apply Coupons List to cart by cartId.
 * @api
 */
interface ApplyCouponsToGuestCartInterface
{
    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param string $cartId The cart mask ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     * @deprecared since 2.0.3, @see \Amasty\Coupons\Api\ApplyCouponsToGuestCartInterface::applyToCart
     */
    public function apply(string $cartId, array $couponCodes): array;

    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons, and did quote items change
     *
     * @param string $cartId The cart ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultListInterface
     */
    public function applyToCart(string $cartId, array $couponCodes): CouponApplyResultListInterface;
}
