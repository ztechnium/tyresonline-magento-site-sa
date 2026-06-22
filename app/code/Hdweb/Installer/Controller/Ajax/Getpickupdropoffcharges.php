<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Installer\Controller\Ajax;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;

class Getpickupdropoffcharges extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;
    protected $_cartModel;
    protected $_addressRepository;
    protected $_customerRepository;
    protected $_checkoutSession;

    /**

     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context, \Magento\Framework\Json\Helper\Data $helper, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Magento\Checkout\Model\Cart $cartModel, \Magento\Framework\App\ResourceConnection $resource, \Magento\Customer\Api\AddressRepositoryInterface $addressRepository, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->helper               = $helper;
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->_cartModel           = $cartModel;
        $this->_resource            = $resource;
        $this->resultRawFactory     = $resultRawFactory;
        $this->_customerRepository  = $customerRepository;
        $this->_addressRepository   = $addressRepository;
        $this->_checkoutSession     = $checkoutSession;
    }

    public function execute()
    {

        $objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
        $locationInfo    = $this->helper->jsonDecode($this->getRequest()->getContent());
        $pickupService   = $locationInfo['pickup_service'];
        $isApplyPickup   = $locationInfo['is_apply_pickup'];
        $pickupType      = $locationInfo['pickup_title'];
        $pickupTypeVal   = $locationInfo['pickup_val'];
        $originLat       = $locationInfo['origin_lat'];
        $originLng       = $locationInfo['origin_lng'];
        $destinationLat  = $locationInfo['destination_lat'];
        $destinationLng  = $locationInfo['destination_lng'];
        $pickupLocation  = $locationInfo['pickup_location'];
		$isdiffDestination 	 = $locationInfo['is_different_destination'];
		$diffDestinationLat  = $locationInfo['diff_destination_lat'];
		$diffDestinationLng  = $locationInfo['diff_destination_lng'];
        $perTripCharge   = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('pickupdropoff/general/trip_charge');
        $perKmTripCharge = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('pickupdropoff/general/km_trip_charge');

        $cart            = $objectManager->get('\Magento\Checkout\Model\Cart');
        $cartId          = $cart->getQuote()->getId();
        $quoteRepository = $objectManager->get('Magento\Quote\Model\QuoteRepository');
        $total           = $objectManager->get('Magento\Quote\Model\Quote\Address\Total');
        $currencysymbol  = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency        = $currencysymbol->getStore()->getCurrentCurrencyCode();
        if ($locationInfo) {
            try {
				$way = '';
                if ($isApplyPickup == 1) {
                    $cartPickupData = array('pickup_val' => $pickupTypeVal, 'pickup_title' => $pickupType, 'pickup_location' => $pickupLocation, 'destination_lat' => $destinationLat, 'destination_lng' => $destinationLng, 'is_different_destination' => $isdiffDestination, 'diff_destination_lat' => $diffDestinationLat, 'diff_destination_lng' => $diffDestinationLng);
                    $this->_checkoutSession->unsCartPickupData();
                    $this->_checkoutSession->setCartPickupData($cartPickupData);
                } else {
                    $this->_checkoutSession->unsCartPickupData();
                }

                /* $distanceData = $this->getDistance($originLat, $originLng, $destinationLat, $destinationLng);
                $distance     = $distanceData['rows'][0]['elements'][0]['distance']['text'];
                $duration     = $distanceData['rows'][0]['elements'][0]['duration']['text'];
                $status       = $distanceData['rows'][0]['elements'][0]['status']; */
                
				if($pickupType == 'Pick-Up + Drop-Off'){
					/* if($isdiffDestination == 1 && $diffDestinationLat != '' && $diffDestinationLng != ''){
						$distanceData = $this->getDistancebyPickupDropoff($destinationLat, $destinationLng, $originLat, $originLng, $diffDestinationLat, $diffDestinationLng);
					}else{
						$distanceData = $this->getDistancebyPickupDropoff($destinationLat, $destinationLng, $originLat, $originLng, $destinationLat, $destinationLng);
					} */
					$way = 'twoway';
					$chargePerCity = $this->getCityChargesfromLatLng($destinationLat, $destinationLng, $diffDestinationLat, $diffDestinationLng, $way, $pickupTypeVal);
				}else{
					//$distanceData = $this->getDistance($pickupType, $originLat, $originLng, $destinationLat, $destinationLng);
					$way = 'oneway';
					$chargePerCity = $this->getCityChargesfromLatLng($destinationLat, $destinationLng, $diffDestinationLat, $diffDestinationLng, $way, $pickupTypeVal);
				}
				
                $distance     = ''; //$distanceData['distance'];
                $duration     = ''; //$distanceData['duration'];
                $status       = ''; //$distanceData['status'];
				
                $distanceKm   = '';
                $chargesText  = '';
				
                if ($pickupService == 1 && $isApplyPickup == 1) {			
					$isPickupDiscount = $objectManager->create('Hdweb\Installer\Helper\Data')->checkPickupDiscount(); //check pickup discount
                    /* $distanceVal   = explode(' mi', $distance);
                    $distanceValKm = round(($distanceVal[0] * 1.609344) * $pickupTypeVal, 2); */
                    /* $pickupCharges = ($pickupTypeVal * $perTripCharge) + ($perKmTripCharge * $distance);
					$pickupCharges = $pickupCharges + ($pickupCharges * 0.05);
                    $pickupCharges = number_format($pickupCharges, 2); */
					
					$pickupCharges = number_format($chargePerCity, 2);
					
                    $status = '';
                    if ($isApplyPickup == 1) {

						if($isPickupDiscount['pickup_discount']){
							$fee = $pickupCharges - ($pickupCharges * ($isPickupDiscount['pickup_discount']/100));
							$pickupCharges = number_format($fee, 2);
						}else{
							$fee = $pickupCharges;
						}
                        $charges     = $currency . number_format($pickupCharges, 2);
                        $status      = 'Applied pick-up/drop-off service!.';
                       // $distanceKm  = 'Total Distance:' . $distance . ' km';
                        $distanceKm  = '';
                        $chargesText = 'Total Charges: ' . $currency . number_format($pickupCharges, 2);
                    } else {
                        $fee     = 0;
                        $charges = $currency . number_format(0.00, 2);
                        $status  = 'Removed pick-up/drop-off service!.';
                    }
                    $response[] = [
                        'distance'     => $distanceKm,
                        'duration'     => $duration,
                        'status'       => $status,
                        'charges'      => $charges,
                        'charges_text' => $chargesText,
                    ];

                    $quote_pickup_detail              		 = array();
                    $quote_pickup_detail['pick_type'] 		 = $pickupType;
                    $quote_pickup_detail['origin_lat']       = $destinationLat;
                    $quote_pickup_detail['origin_lng']       = $destinationLng;
					$quote_pickup_detail['destination_lat']  = $originLat;
                    $quote_pickup_detail['destination_lng']  = $originLng;
					$quote_pickup_detail['is_different_destination']  = $isdiffDestination;
                    $quote_pickup_detail['diff_destination_lat']  = $diffDestinationLat;
                    $quote_pickup_detail['diff_destination_lng']  = $diffDestinationLng;
                    $quote = $quoteRepository->getActive($cartId);
                    $quote->setFee($fee);
                    $total->setTotalAmount('fee', $fee);
                    $total->setBaseTotalAmount('fee', $fee);
                    $total->setFee($fee);
                    $total->setGrandTotal($total->getGrandTotal() + $fee);
                    //$quote->setPickupType($pickupType);
                    $quote->setPickupLocation(serialize($quote_pickup_detail));
                    $quote->save();
                } elseif ($isApplyPickup == 0) {
                    $fee        = 0;
                    $charges    = $currency . number_format(0.00, 2);
                    $status     = 'Removed pick-up/drop-off service!.';
                    $response[] = [
                        'distance'     => '',
                        'duration'     => '',
                        'status'       => $status,
                        'charges'      => $charges,
                        'charges_text' => '',
                    ];
                    $quote = $quoteRepository->getActive($cartId);
                    $quote->setFee($fee);
                    $total->setTotalAmount('fee', $fee);
                    $total->setBaseTotalAmount('fee', $fee);
                    $total->setFee($fee);
                    $total->setGrandTotal($total->getGrandTotal() + $fee);
                    $quote->setPickupLocation('');
                    $quote->save();
                } else {
                    $response[] = [
                        'status' => 'Not available pick-up/drop-off service at this location',
                    ];
                }

                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData($response);

            } catch (Exception $ex) {

            }
        }
    }
	
	public function getDistance($pickupType, $originLat, $originLng, $destinationLat, $destinationLng)
    {
		$objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
		$googleApiKey 	 = $objectManager->create('Hdweb\Installer\Helper\Data')::GOOGLE_API_KEY;
		$origin      = urlencode($originLat . ',' . $originLng);
        $destination = urlencode($destinationLat . ',' . $destinationLng);
		if($pickupType == 'Drop-Off'){
			$destination      = urlencode($originLat . ',' . $originLng);
			$origin = urlencode($destinationLat . ',' . $destinationLng);
		}
		
		$url         = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&key=".$googleApiKey;
        $json        = file_get_contents($url);
        $response    = json_decode($json, true);
		
		$distance     = $response['rows'][0]['elements'][0]['distance']['text'];
		$duration     = $response['rows'][0]['elements'][0]['duration']['text'];
		$status       = $response['rows'][0]['elements'][0]['status']; 
		
		$distanceVal   = explode(' km', $distance);
        $distance = $distanceVal[0];
		$response = array('distance' => $distance, 'duration' => $duration, 'status' => $status);
        return $response;
    }
	
	public function getDistancebyPickupDropoff($originLat1, $originLng1, $installerLat, $installerLng, $destinationLat, $destinationLng)
    {
		$objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
		$googleApiKey 	 = $objectManager->create('Hdweb\Installer\Helper\Data')::GOOGLE_API_KEY;
        $origin      = urlencode($originLat1 . ',' . $originLng1);
        $destination = urlencode($installerLat . ',' . $installerLng);
		
		$origin2      = urlencode($installerLat . ',' . $installerLng);
        $destination2 = urlencode($destinationLat . ',' . $destinationLng);

		$url         = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin|$origin2&destinations=$destination|$destination2&key=".$googleApiKey;
			
        $json        = file_get_contents($url);
        $response    = json_decode($json, true);
		
		$distance     = $response['rows'][0]['elements'][0]['distance']['text'];
		$duration     = $response['rows'][0]['elements'][0]['duration']['text'];
		$status       = $response['rows'][0]['elements'][0]['status']; 
		
		$distance2     = $response['rows'][1]['elements'][1]['distance']['text'];
		$duration2     = $response['rows'][1]['elements'][1]['duration']['text'];
		$status2       = $response['rows'][1]['elements'][1]['status'];
		
		$distanceVal   = explode(' km', $distance);
        $distance1 	   = $distanceVal[0];
		
		$distanceVal2  = explode(' km', $distance2);
        $distance2     = $distanceVal2[0];
		
		$distance = $distance1 + $distance2;
		$duration = $duration.' / '.$duration2;
		$response = array('distance' => $distance, 'duration' => $duration, 'status' => $response['status']);
        return $response;
    }
	
	public function getCityChargesfromLatLng($originLat, $originLng, $diffDestinationLat, $diffDestinationLng, $way, $pickupTypeVal)
    {
		$objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
		$googleApiKey 	 = $objectManager->create('Hdweb\Installer\Helper\Data')::GOOGLE_API_KEY;
		
		$charge = 0;
		$twowayCharge = 0;
		$chargesPerCity = array('Dubai' => array('oneway' => 105, 'twoway' => 210), 'Abu Dhabi' => array('oneway' => 284, 'twoway' => 567), 'Al Ain' => array('oneway' => 231, 'twoway' => 462), 'Ajman' => array('oneway' => 158, 'twoway' => 315), 'Fujairah' => array('oneway' => 231, 'twoway' => 462), 'Sharjah' => array('oneway' => 116, 'twoway' => 231), 'Ras Al-Khaimah' => array('oneway' => 189, 'twoway' => 378), 'Ras al Khaimah' => array('oneway' => 189, 'twoway' => 378), 'Umm Al Quwain' => array('oneway' => 173, 'twoway' => 346));
		
		if($diffDestinationLat != '' && $diffDestinationLng != '' && $pickupTypeVal == 2){
			$diffurl         = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$diffDestinationLat,$diffDestinationLng&key=".$googleApiKey;
			$diffjson        = file_get_contents($diffurl);
			$diffoutput    = json_decode($diffjson, true);		
			
			for($j=0;$j<count($diffoutput['results'][0]['address_components']);$j++){
			   $cn = array($diffoutput['results'][0]['address_components'][$j]['types'][0]);
			  // if(in_array("locality", $cn))
			   if(in_array("administrative_area_level_1", $cn))
			   {
				$city = $diffoutput['results'][0]['address_components'][$j]['long_name'];
				if (array_key_exists($city,$chargesPerCity)){
					$twowayCharge = $chargesPerCity[$city][$way];
				}
			   }
			}
		}
		$url         = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$originLat,$originLng&key=".$googleApiKey;
		$json        = file_get_contents($url);
		$output    = json_decode($json, true);		
		
		for($j=0;$j<count($output['results'][0]['address_components']);$j++){
		   $cn = array($output['results'][0]['address_components'][$j]['types'][0]);
		  // if(in_array("locality", $cn))
		   if(in_array("administrative_area_level_1", $cn))
		   {
			$city = $output['results'][0]['address_components'][$j]['long_name'];
			if (array_key_exists($city,$chargesPerCity)){
				$charge = $chargesPerCity[$city][$way];
			}
		   }
		}
		
		if($charge == 0 && $twowayCharge == 0){
			if($way == 'oneway'){
				$charge = $chargesPerCity['Abu Dhabi'][$way];
			}else{
				$twowayCharge = $chargesPerCity['Abu Dhabi'][$way];
			}
		}
		
		$finalCharges = max(array($charge, $twowayCharge));
				
		return $finalCharges;
    }
}
