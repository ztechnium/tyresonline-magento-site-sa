<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Installer\Controller\Ajax;

use Magento\Customer\Api\AccountManagementInterface;

class Savecartinstaller extends \Magento\Framework\App\Action\Action
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
    protected $_resource;
    protected $_storeinsatllerModel;
    protected $_addressRepository;
    protected $_customerRepository;
    protected $_checkoutSession;
    protected $collectionFactory;
    protected $json;

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
        \Magento\Framework\App\Action\Context $context, \Magento\Framework\Json\Helper\Data $helper, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, \Magento\Checkout\Model\Cart $cartModel, \Magento\Framework\App\ResourceConnection $resource, \Magento\Customer\Api\AddressRepositoryInterface $addressRepository, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Checkout\Model\Session $checkoutSession,
        \Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory $collectionFactory,
         \Magento\Framework\Serialize\Serializer\Json $json
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
        $this->collectionFactory = $collectionFactory;
        $this->json = $json;
    }

    public function execute()
    {
        
       // $post_Param = $this->getRequest()->getPost();
        $post_Param = $this->json->unserialize($this->getRequest()->getContent());

        if (isset($post_Param['pickup_store']) && isset($post_Param['pickup_date']) && isset($post_Param['pickup_time'])) {
            $storeid = $post_Param['pickup_store'];

            $pickup_store = $post_Param['pickup_store'];
            $pickup_date  = $post_Param['pickup_date'];
            $pickup_time  = $post_Param['pickup_time'];

            $quote = $this->_checkoutSession->getQuote();
            $quote->setPickupDate($pickup_date);
            $quote->setPickupTime($pickup_time);
            $quote->setPickupStore($pickup_store);

            $quote->setDeliveryDate($pickup_date);
            $quote->setDeliveryComment($pickup_time);

            $quote->save();

            $this->_checkoutSession->setPickupdate($pickup_date);
            $this->_checkoutSession->setPickuptime($pickup_time);
            $this->_checkoutSession->setPickupstoreid($pickup_store);

            $response=array();

            $collection = $this->collectionFactory->create();
            $collection->addActiveFilter();
            $collection->AddFieldToFilter("stores_id", (int) $pickup_store);
            if ($collection) {
                 $store_info = $collection->getFirstItem();
                 $store_data = $store_info->getData();
                 $response = [
                            'message' 			=> 'success',
                            'name' 				=> $store_data['name'] ?? '',
                            'name_rtl'              => $store_data['name_rtl'] ?? ($store_data['name'] ?? ''),
                            'address' 			=> $store_data['address'] ?? '',
                            'address_rtl'           => $store_data['address_rtl'] ?? ($store_data['address'] ?? ''),
                            'installer_lat' 	=> $store_data['latitude'] ?? '',
                            'installer_lng' 	=> $store_data['longitude'] ?? '',
                            'pickup_service'	=> $store_data['pickup_service'] ?? 0,
                            'installer_date' 	=> $pickup_date,
                            'installer_time' 	=> $pickup_time
                        ];

                 $resultJson = $this->resultJsonFactory->create();
                 return $resultJson->setData($response);         
            }else{
                     $response = [
                        'message' => 'fail',
                    ];
            }         
        }elseif(isset($post_Param['installer_id']) && $post_Param['installer_id'] == 0 ){

            $quote = $this->_checkoutSession->getQuote();
            $quote->setPickupDate('');
            $quote->setPickupTime('');
            $quote->setPickupStore('');

            $quote->setDeliveryDate('');
            $quote->setDeliveryComment('');

            $quote->save();

            $this->_checkoutSession->unsetPickupdate();
            $this->_checkoutSession->unsetPickuptime();
            $this->_checkoutSession->unsetPickupstoreid();

            $response=array();

                     $response = [
                        'message' => 'success',
                    ];
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($response);
        }else {
              $response = [
                        'message' => 'fail',
                    ];
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($response);
        }  
    }
}
