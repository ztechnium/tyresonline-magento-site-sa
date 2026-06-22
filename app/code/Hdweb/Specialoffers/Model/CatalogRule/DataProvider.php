<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Specialoffers\Model\CatalogRule;

use Magento\CatalogRule\Model\ResourceModel\Rule\Collection;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Rule $rule */
        foreach ($items as $rule) {
            $rule->load($rule->getId());
            $this->loadedData[$rule->getId()] = $rule->getData();

            //Parth Shah Start :- Display Image on Edit Form Catalog
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currentStore = $storeManager->getStore();
            $media_url=$currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            if ($rule['rule_banner']) {
                $m['rule_banner_image'][0]['name'] = $rule['rule_banner'];
                $m['rule_banner_image'][0]['url'] = $media_url."catalogrule/offerimage/".$rule['rule_banner'];
                $fullData = $this->loadedData;
                $this->loadedData[$rule->getId()] = array_merge($fullData[$rule->getId()], $m);
            }
            if ($rule['rule_product_banner']) {
                $m['rule_product_banner'][0]['name'] = $rule['rule_product_banner'];
                $m['rule_product_banner'][0]['url'] = $media_url."catalogrule/offerimage/".$rule['rule_product_banner'];
                $fullData = $this->loadedData;
                $this->loadedData[$rule->getId()] = array_merge($fullData[$rule->getId()], $m);
            }
            if ($rule['rule_product_bundle_banner']) {
                $m['rule_product_bundle_banner'][0]['name'] = $rule['rule_product_bundle_banner'];
                $m['rule_product_bundle_banner'][0]['url'] = $media_url."catalogrule/offerimage/".$rule['rule_product_bundle_banner'];
                $fullData = $this->loadedData;
                $this->loadedData[$rule->getId()] = array_merge($fullData[$rule->getId()], $m);
            }
            if ($rule['rule_mobile_banner']) {
                $m['rule_mobile_banner'][0]['name'] = $rule['rule_mobile_banner'];
                $m['rule_mobile_banner'][0]['url'] = $media_url."catalogrule/offerimage/".$rule['rule_mobile_banner'];
                $fullData = $this->loadedData;
                $this->loadedData[$rule->getId()] = array_merge($fullData[$rule->getId()], $m);
            }
			if ($rule['bundle_rule_banner']) {
                $m['rule_product_bundle_banner'][0]['name'] = $rule['bundle_rule_banner'];
                $m['rule_product_bundle_banner'][0]['url'] = $media_url."catalogrule/offerimage/".$rule['bundle_rule_banner'];
                $fullData = $this->loadedData;
                $this->loadedData[$rule->getId()] = array_merge($fullData[$rule->getId()], $m);
            }
            //Parth Shah End
        }

        $data = $this->dataPersistor->get('catalog_rule');
        if (!empty($data)) {
            $rule = $this->collection->getNewEmptyItem();
            $rule->setData($data);
            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->dataPersistor->clear('catalog_rule');
        }

        return $this->loadedData;
    }
}
