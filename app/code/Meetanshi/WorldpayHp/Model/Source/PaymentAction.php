<?php

namespace Meetanshi\WorldpayHp\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package Meetanshi\WorldpayHp\Model\Source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'sale',
                'label' => __('Authorize and Capture')
            ],
            [
                'value' => 'authorization',
                'label' => __('Authorize'),
            ],
        ];
    }
}
