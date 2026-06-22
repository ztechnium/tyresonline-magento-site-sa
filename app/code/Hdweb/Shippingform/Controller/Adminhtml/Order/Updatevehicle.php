<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Shippingform\Controller\Adminhtml\Order;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\ResultFactory; 

/*use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;*/

class Updatevehicle extends \Magento\Framework\App\Action\Action {

    protected $helper;
    protected $_orderRepository;
    protected $_messageManager;

    public function __construct(
    \Magento\Framework\App\Action\Context $context, 
    \Magento\Framework\Json\Helper\Data $helper, 
    \Magento\Framework\App\RequestInterface $request,
    OrderRepositoryInterface $orderRepository,
    \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->request = $request;
        $this->_orderRepository = $orderRepository;
        $this->_messageManager = $messageManager;
    }

    public function execute() {

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $new = 0;
        $vehicleDetails = array();
        $vehicleDetails['vechicle_data'] = array('Plate' => $this->request->getParam('plate_number'), 'Make' => $this->request->getParam('vehiclelist1'), 'Year' => $this->request->getParam('vehicleyear1'), 'Model' => $this->request->getParam('vehiclemodel1'),'Vin' => $this->request->getParam('vin_number'));
        $finalVehicleData = serialize($vehicleDetails);
        // print_r($finalVehicleData);
        $order = $this->_orderRepository->get($this->request->getParam('order_id'));
        if ($order->getVehicleDetails()) {
            $new = 1;
        }
        $order->setVehicleDetails($finalVehicleData)
              ->setMake($this->request->getParam('vehiclelist1'))
              ->setModel($this->request->getParam('vehiclemodel1'))
              ->setYear($this->request->getParam('vehicleyear1'))
              ->setPlate($this->request->getParam('plate_number'))
              ->setVin($this->request->getParam('vin_number'));
        $order->save();
        if ($new = 0) {
            $this->_messageManager->addSuccessMessage('Vehicle Added Successfully');
        }else{
            $this->_messageManager->addSuccessMessage('Vehicle Updated Successfully');
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;

    }

}
