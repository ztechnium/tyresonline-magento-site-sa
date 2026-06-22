<?php

declare(strict_types=1);

namespace Amasty\Coupons\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;

class CartItemsSnapshotManagerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var CartItemsSnapshotManager
     */
    private $cartItemsSnapshotManager;

    public function setUp(): void
    {
        $this->cartItemsSnapshotManager = new CartItemsSnapshotManager();
    }

    /**
     * @param array $itemsData
     * @param array $expectedResult
     * @return void
     * @covers \Amasty\Coupons\Model\Quote\CartItemsSnapshotManager::takeSnapshot()
     * @dataProvider takeSnapshotDataProvider
     */
    public function testTakeSnapshot(array $itemsData, array $expectedResult)
    {
        $cart = $this->getCartWithItems($itemsData);

        $actualResult = $this->cartItemsSnapshotManager->takeSnapshot($cart);

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @param array $itemsData
     * @param array $snapshot
     * @param bool $expectedResult
     * @return void
     * @covers \Amasty\Coupons\Model\Quote\CartItemsSnapshotManager::isEqualWithSnapshot()
     * @dataProvider isEqualWithSnapshotDataProvider
     */
    public function testIsEqualWithSnapshot(array $itemsData, array $snapshot, bool $expectedResult): void
    {
        $cart = $this->getCartWithItems($itemsData);
        $actualResult = $this->cartItemsSnapshotManager->isEqualWithSnapshot($cart, $snapshot);

        $this->assertSame($expectedResult, $actualResult);
    }

    public function takeSnapshotDataProvider(): array
    {
        return [
            'correctSnapshot' => [
                'itemsData' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'expectedResult' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ]
            ],

            'correctSnapshotSortOrder' => [
                'itemsData' => [
                    ['sku' => 'b', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'a', 'qty' => 1.0],
                ],
                'expectedResult' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ]
            ],
        ];
    }

    public function isEqualWithSnapshotDataProvider(): array
    {
        return [
            'correctSnapshot' => [
                'itemsData' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'snapshot' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'expectedResult' => true,
            ],

            'correctSnapshotSortOrder' => [
                'itemsData' => [
                    ['sku' => 'b', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'a', 'qty' => 1.0],
                ],
                'snapshot' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'expectedResult' => true,
            ],
            'diffInItems' => [
                'itemsData' => [
                    ['sku' => 'b', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'a', 'qty' => 1.0],
                ],
                'snapshot' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'expectedResult' => false,
            ],
            'diffInQtys' => [
                'itemsData' => [
                    ['sku' => 'b', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 2.0],
                    ['sku' => 'a', 'qty' => 1.0],
                ],
                'snapshot' => [
                    ['sku' => 'a', 'qty' => 1.0],
                    ['sku' => 'a', 'qty' => 3.0],
                    ['sku' => 'b', 'qty' => 1.0],
                ],
                'expectedResult' => false,
            ]
        ];
    }

    private function getCartWithItems(array $itemsData): CartInterface
    {
        $cartItems = [];
        foreach ($itemsData as $itemData) {
            $cartItem = $this->createMock(CartItemInterface::class);
            $cartItem->method('getSku')->willReturn($itemData['sku']);
            $cartItem->method('getQty')->willReturn($itemData['qty']);
            $cartItems[] = $cartItem;
        }

        $cart = $this->createMock(Quote::class);
        $cart->expects($this->once())->method('getAllItems')->willReturn($cartItems);

        return $cart;
    }
}
