<?php
/**
 * Ecomteck_StorePickup Magento Extension
 *
 * @category    Ecomteck
 * @package     Ecomteck_StorePickup
 * @author      Ecomteck <ecomteck@gmail.com>
 * @website    http://www.ecomteck.com
 */

namespace Ecomteck\StorePickup\Observer;

use Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory;
use Magento\Framework\Event\ObserverInterface;

class DataAssignObserver implements ObserverInterface
{
    protected $quoteRepository;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        CollectionFactory $collectionFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
        $this->quoteRepository   = $quoteRepository;
        $this->collectionFactory = $collectionFactory;
        $this->regionFactory     = $regionFactory;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();

        $order->setPickupDate($quote->getPickupDate());
        $order->setPickupTime($quote->getPickupTime());

        $order->setPlate($quote->getPlate());
        $order->setMake($quote->getMake());
        $order->setModel($quote->getModel());
        $order->setYear($quote->getYear());
        $order->setVinNumber($quote->getVinNumber());
        $order->setPickupLocation($quote->getPickupLocation());

        if ($quote->getPickupStore()) {
            $order->setPickupStore($quote->getPickupStore());
            /* $collection = $this->collectionFactory->create();
            $collection->addActiveFilter();
            $collection->AddFieldToFilter("stores_id", (int) $quote->getPickupStore());

            if ($collection) {
                $store_info = $collection->getFirstItem();
                $store_data = $store_info->getData();

                if ($store_data) {
                    $country_id = isset($store_data['country']) ? $store_data['country'] : '';
                    $city       = isset($store_data['city']) ? $store_data['city'] : '';
                    $postcode   = isset($store_data['postcode']) ? $store_data['postcode'] : '';
                    $region     = isset($store_data['region']) ? $store_data['region'] : '';
                    $region_id  = $this->getRegionByName($region, $country_id);
                    $street     = isset($store_data['address']) ? $store_data['address'] : '';
                    $order->getShippingAddress()
                        ->setCountryId((string) $country_id)
                        ->setCity((string) $city)
                        ->setPostcode((string) $postcode)
                        ->setRegionId((string) $region_id)
                        ->setRegion((string) $region)
                        ->setStreet($street)
                        ->setCollectShippingRates(true)
                        ->save();
                }
            } */
        }
		if($quote->getMobilevanserviceLocation()){
			$order->setMobilevanserviceLocation($quote->getMobilevanserviceLocation());
			setcookie("mobilevanservice_data", "", time() - 3600, '/');
		}
        return $this;
    }
    public function getRegionByName($region, $countryId)
    {
        $region_object = $this->regionFactory->create()->loadByName($region, $countryId);
        if ($region_object) {
            return $region_object->getRegionId();
        }
        return "";
    }
}
