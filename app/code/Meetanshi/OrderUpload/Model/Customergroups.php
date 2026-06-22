<?php

namespace Meetanshi\OrderUpload\Model;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Customergroups
 * @package Meetanshi\OrderUpload\Model
 */
class Customergroups implements ArrayInterface
{
    /**
     * @var
     */
    protected $options;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Customergroups constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->objectManager->create('Magento\Customer\Model\ResourceModel\Group\Collection')->setRealGroupsFilter()->loadData()->toOptionArray();

            array_unshift($this->options, ['value' => '0', 'label' => __('NOT LOGGED IN')]);
        }

        return $this->options;
    }
}
