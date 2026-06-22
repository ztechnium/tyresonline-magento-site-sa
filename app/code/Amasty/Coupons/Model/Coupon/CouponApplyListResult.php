<?php

declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

use Amasty\Coupons\Api\Data\CouponApplyResultListInterface;

class CouponApplyListResult implements CouponApplyResultListInterface
{
    /**
     * @var \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     */
    private $items;

    /**
     * @var bool
     */
    private $isQuoteItemsChanged;

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getIsQuoteItemsChanged(): bool
    {
        return $this->isQuoteItemsChanged;
    }

    public function setIsQuoteItemsChanged(bool $isQuoteItemsChanged): void
    {
        $this->isQuoteItemsChanged = $isQuoteItemsChanged;
    }
}
