<?php

declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

use Amasty\Coupons\Api\Data\CouponApplyResultListInterface;

class ApplyCouponsToGuestCart implements \Amasty\Coupons\Api\ApplyCouponsToGuestCartInterface
{
    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var \Amasty\Coupons\Api\ApplyCouponsToCartInterface
     */
    private $applyCouponsToCart;

    public function __construct(
        \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Amasty\Coupons\Api\ApplyCouponsToCartInterface $applyCouponsToCart
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->applyCouponsToCart = $applyCouponsToCart;
    }

    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param string $cartId The cart mask ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     */
    public function apply(string $cartId, array $couponCodes): array
    {
        return $this->applyCouponsToCart->applyToCart(
            $this->maskedQuoteIdToQuoteId->execute($cartId),
            $couponCodes
        )->getItems();
    }

    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param string $cartId The cart mask ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultListInterface
     */
    public function applyToCart(string $cartId, array $couponCodes): CouponApplyResultListInterface
    {
        return $this->applyCouponsToCart->applyToCart($this->maskedQuoteIdToQuoteId->execute($cartId), $couponCodes);
    }
}
