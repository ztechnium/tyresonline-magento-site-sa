<?php

namespace Magecomp\Extrafee\Controller\Cart;

class Index extends \Magento\Checkout\Controller\Cart\Index
{
    /**
     * Shopping cart display action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $quote = $this->_checkoutSession->getQuote();
		$this->_checkoutSession->unsCartPickupData();
		$fee = 0.00;
		$quote->setFee($fee);
		$quote->setPickupLocation('');
		$quote->setDeliveryDate('');
		$quote->setDeliveryComment('');
		$quote->setPickupDate('');
		$quote->setPickupTime('');
		$quote->setPickupStore('');
		$quote->setMobilevanserviceLocation('');
		//$quote->save();
		
		if(isset($_COOKIE['mobilevanservice_data'])){
			$cookieValue = stripslashes($_COOKIE['mobilevanservice_data']);
			$serviceData = json_decode($cookieValue, true);
			$mobilevanserviceAddress = $serviceData['mobilevanservice_address'];
			$mobilevanserviceLocation_lat = $serviceData['mobilevanservice_location_lat'];
			$mobilevanserviceLocation_lng = $serviceData['mobilevanservice_location_lng'];
			$mobilevanserviceDate = $serviceData['mobilevanservice_date'];
			$mobilevanserviceTime = $serviceData['mobilevanservice_time'];
			$mobilevanserviceNotes = $serviceData['mobilevanservice_notes'];
			$objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
			$mobileVanInstallerId = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('installer/general/mobilevan_fitment_service_installer');
			 
			if($mobileVanInstallerId){
				$mobileVanServiceData = array();
				$mobileVanServiceData['address'] = $mobilevanserviceAddress;
				$mobileVanServiceData['lat'] = $mobilevanserviceLocation_lat;
				$mobileVanServiceData['long'] = $mobilevanserviceLocation_lng;
				$mobileVanServiceData['date'] = $mobilevanserviceDate;
				$mobileVanServiceData['time'] = $mobilevanserviceTime;
				$mobileVanServiceData['notes'] = $mobilevanserviceNotes;
				
				$quote->setPickupDate($mobilevanserviceDate);
				$quote->setPickupTime($mobilevanserviceTime);
				$quote->setPickupStore($mobileVanInstallerId);
				$quote->setDeliveryDate($mobilevanserviceDate);
				$quote->setDeliveryComment($mobilevanserviceTime);
				$this->_checkoutSession->setPickupdate($mobilevanserviceDate);
				$this->_checkoutSession->setPickuptime($mobilevanserviceTime);
				$this->_checkoutSession->setPickupstoreid($mobileVanInstallerId);
				$quote->setMobilevanserviceLocation(serialize($mobileVanServiceData));
				$quote->save();
			}				
		}
		
		$quote->collectTotals();
		$quote->save();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Shopping Cart'));
        return $resultPage;
    }
}