<?php

namespace Ecomteck\StorePickup\Block;

use Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory;
use Magento\Catalog\Block\Product\Context;

class StorePickup extends \Magento\Framework\View\Element\Template
{

    protected $collectionFactory;
    protected $storeManagerInterface;
    protected $storescollection;
    protected $jsonHelper;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        CollectionFactory $collectionFactory,
        \Ecomteck\StoreLocator\Model\Stores $storescollection,
        Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data = []
    ) {
        $this->storeManagerInterface = $storeManagerInterface;
        $this->collectionFactory     = $collectionFactory;
        $this->storescollection      = $storescollection;
        $this->jsonHelper            = $jsonHelper;
        parent::__construct($context, $data);
    }

    public function getStorePickupList()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storescollection = $this->storescollection->getCollection();
        $stores = $storescollection->addFieldToFilter('status', 1);
        return $stores;
    }
    public function getStorePickupMediaPath()
    {
        $media_dir            = $this->storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $storePickupImagepath = $media_dir . 'ecomteck_storelocator/stores/image';
        return $storePickupImagepath;
    }

    public function decodeOpeningTime($opening_hours)
    {
        $opening_hours = $this->jsonHelper->jsonDecode($opening_hours);
        return $opening_hours;
    }

}
