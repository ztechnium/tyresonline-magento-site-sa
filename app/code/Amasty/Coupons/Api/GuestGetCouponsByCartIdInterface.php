<?php

namespace Amasty\Coupons\Api;

/**
 * Get Coupons List by cartId.
 * @api
 */
interface GuestGetCouponsByCartIdInterface
{
    /**
     * Return list of applied coupons in a specified cart.
     *
     * @param string $cartId The cart mask ID.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     */
    public function get(string $cartId): array;
}
