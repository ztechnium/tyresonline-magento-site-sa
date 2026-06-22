<?php

namespace Amasty\Coupons\Api;

/**
 * Get Coupons List by cartId/quoteId.
 * @api
 */
interface GetCouponsByCartIdInterface
{
    /**
     * Return list of applied coupons in a specified cart.
     *
     * @param int $cartId The cart ID.
     * @return string[]
     */
    public function get(int $cartId): array;
}
