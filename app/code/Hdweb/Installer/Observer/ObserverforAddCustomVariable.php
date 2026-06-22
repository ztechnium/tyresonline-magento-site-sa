<?php
namespace Hdweb\Installer\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class ObserverforAddCustomVariable implements ObserverInterface
{
    protected $orderModel;
    protected $countryModel;
    protected $pickupstores;
    protected $country;

    public function __construct(
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Directory\Model\Country $countryModel,
        \Ecomteck\StoreLocator\Model\Stores $pickupstores,
        \Magento\Directory\Model\Country $country
    ){
        $this->orderModel = $orderModel;
        $this->countryModel = $countryModel;
        $this->pickupstores    = $pickupstores;
        $this->country    = $country;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $controller */
        $transport = $observer->getTransport();
        $order_id =  $transport['order']->getId();
        $order = $this->orderModel->load($order_id);
        $installer_id = $order->getPickupStore();
        $pickupstoresData = $this->pickupstores->load($installer_id);
        $country = $this->country->load($pickupstoresData->getCountry())->getName();
        $transport['order_comment'] = $order->getEcomteckOrderComment();
        $transport['installer_name'] = $pickupstoresData->getName();
        $transport['installer_name_ar'] = $pickupstoresData->getNameRtl();
        $transport['installer_street'] = $pickupstoresData->getAddress();
        $transport['installer_street_ar'] = $pickupstoresData->getAddressRtl();
        $transport['installer_city'] = $pickupstoresData->getCity();
        $transport['installer_city_ar'] = $pickupstoresData->getCityRtl();
        $transport['installer_country'] = $country;
        $transport['installer_location_map'] = $pickupstoresData->getExternalLink();
        $pickupDate = str_replace('00:00:00', '', $order->getPickupDate());
        $transport['pickup_date'] = $pickupDate;
        $transport['pickup_time'] = $order->getPickupTime();
        if ($order->getPickupLocation()){
            $pickup_detail = unserialize($order->getPickupLocation());
            $transport['pickup_type'] = $pickup_detail['pick_type'];
            if(isset($pickup_detail['origin_lat']) && isset($pickup_detail['origin_lng']) && isset($pickup_detail['destination_lat']) && isset($pickup_detail['destination_lng'])){
                $originlatlong = $pickup_detail['origin_lat'].','.$pickup_detail['origin_lng'];
                $destinationlatlong = $pickup_detail['destination_lat'].','.$pickup_detail['destination_lng'];
                $distanceRoute = $originlatlong.'/'.$destinationlatlong;
                $isdiffDestination = isset($pickup_detail['is_different_destination']);
                $pickupLocation = 'https://www.google.com/maps/dir/'.$distanceRoute;
                $transport['pickup_location'] = $pickupLocation;
                if($pickup_detail['pick_type'] == 'Pick-Up + Drop-Off'){
                    //if($isdiffDestination == 1){
                    if(!empty($pickup_detail['is_different_destination'])){
                        $originlatlong2 = $pickup_detail['destination_lat'].','.$pickup_detail['destination_lng'];
                        $destinationlatlong2 = $pickup_detail['diff_destination_lat'].','.$pickup_detail['diff_destination_lng'];
                        $distanceRoute2 = $originlatlong2.'/'.$destinationlatlong2;

                        $transport['diff_drop_off_location'] = 'https://www.google.com/maps/dir/'.$distanceRoute2;
                    } else {
                        $originlatlong2 = $pickup_detail['destination_lat'].','.$pickup_detail['destination_lng'];
                        $destinationlatlong2 = $pickup_detail['origin_lat'].','.$pickup_detail['origin_lng'];
                        $distanceRoute2 = $originlatlong2.'/'.$destinationlatlong2;
                        $transport['drop_off_location'] = 'https://www.google.com/maps/dir/'.$distanceRoute2;
                    }
                }
            }
        }
		if($order->getMobilevanserviceLocation()){
			$serviceData = unserialize($order->getMobilevanserviceLocation());
			$mobilevanserviceAddress = $serviceData['address'];
			$mobilevanserviceLocation_lat = $serviceData['lat'];
			$mobilevanserviceLocation_lng = $serviceData['long'];
			$latLong = $mobilevanserviceLocation_lat.','.$mobilevanserviceLocation_lng;
			$mobilevanserviceLocation_notes = $serviceData['notes'];
			$transport['mobilevanservice_address'] = $mobilevanserviceAddress;
			$transport['mobilevanservice_location'] = 'http://maps.google.com/?q='.$latLong;
			$transport['mobilevanservice_notes'] = $mobilevanserviceLocation_notes;
		}
        $transport['vehicle_make'] = $order->getMake();
        $transport['vehicle_model'] = $order->getModel();
        $transport['vehicle_year'] = $order->getYear();
        $transport['vehicle_plate'] = $order->getPlate();
        $transport['vehicle_vin_no'] = $order->getVinNumber();
    }
}