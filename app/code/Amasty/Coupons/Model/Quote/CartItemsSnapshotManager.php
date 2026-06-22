<?php

declare(strict_types=1);

namespace Amasty\Coupons\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;

class CartItemsSnapshotManager
{
    /**
     * @param CartInterface $cart
     * @return array
     */
    public function takeSnapshot(CartInterface $cart): array
    {
        $itemsSnapshot = [];

        foreach ($cart->getAllItems() as $item) {
            $itemsSnapshot[] = [
                'sku' => $item->getSku(),
                'qty' => $item->getQty()
            ];
        }

        usort($itemsSnapshot, function ($itemA, $itemB) {
            $cmp = strcmp($itemA['sku'], $itemB['sku']);

            if ($cmp === 0) {
                $cmp = $itemA['qty'] <=> $itemB['qty'];
            }

            return $cmp;
        });

        return $itemsSnapshot;
    }

    /**
     * @param CartInterface $cart
     * @param array $snapshot
     * @return bool
     */
    public function isEqualWithSnapshot(CartInterface $cart, array $snapshot): bool
    {
        $currentSnapshot = $this->takeSnapshot($cart);

        return $currentSnapshot === $snapshot;
    }
}
