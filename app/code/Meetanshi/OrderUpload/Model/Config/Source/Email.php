<?php

namespace Meetanshi\OrderUpload\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Email
 * @package Meetanshi\OrderUpload\Model\Config\Source
 */
class Email implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '1', 'label' => __('Separate Email')], ['value' => '2', 'label' => __('Attach with order email')]];
    }
}
