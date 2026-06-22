<?php
namespace Hdweb\Shippingform\Model\Custom\Source;

class Country implements \Magento\Framework\Data\OptionSourceInterface
{
	/**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * Return array of options as value-label pairs.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $option = [];
        /** @var \Magento\Directory\Model\ResourceModel\Country\Collection $collection */
        $collection = $this->_countryCollectionFactory->create()->loadByStore();
        $option[] = ['label' => '', 'value' => ''];
        foreach ($collection as $item) {
            $option[] = ['label' => $item->getName(), 'value' => $item->getId()];
        }

        return $option;
    }
}
