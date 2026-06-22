<?php

declare(strict_types=1);

namespace Amasty\Coupons\Api\Data;

interface CouponApplyResultListInterface
{
    /**
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     */
    public function getItems(): array;

    /**
     * @param \Amasty\Coupons\Api\Data\CouponApplyResultInterface[] $items
     * @return void
     */
    public function setItems(array $items): void;

    /**
     * @return bool
     */
    public function getIsQuoteItemsChanged(): bool;

    /**
     * @param bool $isQuoteItemsChanged
     * @return void
     */
    public function setIsQuoteItemsChanged(bool $isQuoteItemsChanged): void;
}
