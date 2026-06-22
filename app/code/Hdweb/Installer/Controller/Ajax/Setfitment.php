<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Installer\Controller\Ajax;

use Magento\Framework\Exception\LocalizedException;

class Setfitment extends \Magento\Framework\App\Action\Action {

    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    protected $_scopeConfig;
	protected $_objectManager;
	protected $_cartModel;
	protected $pickupstores;
	protected $json;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    public function __construct(
    \Magento\Framework\App\Action\Context $context,
	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	\Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
	\Magento\Checkout\Model\Session $checkoutSession,
	\Magento\Framework\ObjectManagerInterface $objectManager,
	\Magento\Checkout\Model\Cart $cartModel,
	\Ecomteck\StoreLocator\Model\Stores $pickupstores,
	 \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_checkoutSession = $checkoutSession;
		$this->_objectManager 	= $objectManager;
		$this->_cartModel       = $cartModel;
		$this->pickupstores    = $pickupstores;
		$this->json = $json;
    }

    public function execute() {
		$postData = $this->json->unserialize($this->getRequest()->getContent());
		$isFitment = $postData['fitment_installer'];	
		$pickup_store = $postData['installer_id'];	
		$this->_checkoutSession->unsIsFitmentData();		
		$this->_checkoutSession->setIsFitmentData($isFitment);
		$quote = $this->_cartModel->getQuote();
		$total = $this->_objectManager->get('Magento\Quote\Model\Quote\Address\Total');
		$installer_detail = $this->pickupstores->load($pickup_store);
		if($isFitment == 0 && $pickup_store != ''){
			$pickup_date = date('m/d/y', strtotime(' + 3 day'));
            $pickup_time  = 'pm';
            $quote->setPickupDate($pickup_date);
            $quote->setPickupTime($pickup_time);
            $quote->setPickupStore($pickup_store);
            $quote->setDeliveryDate($pickup_date);
            $quote->setDeliveryComment($pickup_time);
            $quote->save();
            $this->_checkoutSession->setPickupdate($pickup_date);
            $this->_checkoutSession->setPickuptime($pickup_time);
            $this->_checkoutSession->setPickupstoreid($pickup_store);
			$response = [
				'no_fitting' 	    => 1,
				'name' 				=> $installer_detail->getName(),
				'address' 			=> $installer_detail->getAddress(),
				'installer_lat' 	=> $installer_detail->getLatitude(),
				'installer_lng' 	=> $installer_detail->getLongitude(),
				'pickup_service'	=> $installer_detail->getPickupService(),
				'installer_date' 	=> $pickup_date,
				'installer_time' 	=> $pickup_time
			];
		}else{
			$quote->setPickupDate('');
            $quote->setPickupTime('');
            $quote->setPickupStore('');
            $quote->setDeliveryDate('');
            $quote->setDeliveryComment('');
            $quote->save();
            $this->_checkoutSession->setPickupdate('');
            $this->_checkoutSession->setPickuptime('');
            $this->_checkoutSession->setPickupstoreid('');
			$response = [
				'no_fitting'	=> 0
			];
		}
		$resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}