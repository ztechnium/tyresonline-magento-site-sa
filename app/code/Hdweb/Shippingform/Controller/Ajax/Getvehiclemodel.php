<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Shippingform\Controller\Ajax;

/*use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;*/

class Getvehiclemodel extends \Magento\Framework\App\Action\Action
{

    protected $helper;
    protected $resultJsonFactory;
    protected $cartModel;
    protected $customerRepository;
    protected $finderhelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Hdweb\Tyrefinder\Helper\Data $finderhelper
    ) {
        parent::__construct($context);
        $this->helper              = $helper;
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->_cartModel          = $cartModel;
        $this->_customerRepository = $customerRepository;
        $this->finderhelper        = $finderhelper;
    }

    public function execute()
    {

        $vehiclemakes = $this->helper->jsonDecode($this->getRequest()->getContent());
        if ($vehiclemakes) {
            try {			
				$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
				$VehicleModel_url = "https://api.wheel-size.com/v2/models/?user_key=".$wheelApiKey."&make=".$vehiclemakes. "&region=medm";
				$WheelVehicleModel = file_get_contents($VehicleModel_url);
				$WheelVehicleModel =json_decode($WheelVehicleModel);
				
                $modelSelectHtml   = '<option value="">' . __('Select Model') . '</option>';
                $yearSelectHtml    = '<option value="">' . __('Select Year') . '</option>';
				
				if ($WheelVehicleModel && !empty($WheelVehicleModel->data) && count($WheelVehicleModel->data) > 0) {
					foreach ($WheelVehicleModel->data as $modelOption) {
						$modelSelectHtml .= '<option value="' . $modelOption->slug . '">' . $modelOption->name . '</option>';
					}
				}
                $response[] = [
                    'vehiclemodel' => $modelSelectHtml,
                    'vehicleyear'  => $yearSelectHtml,
                ];
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData($response);

            } catch (Exception $ex) {

            }
        }
    }
}
