<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Shippingform\Controller\Ajax;

/*use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;*/

class Getvehicleyear extends \Magento\Framework\App\Action\Action
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
        $this->finderhelper          = $finderhelper;
    }

    public function execute()
    {

        $vehiclemodel = $this->helper->jsonDecode($this->getRequest()->getContent());

        $make         = $vehiclemodel['make'];
        $vehiclemodel = $vehiclemodel['model'];
        if ($vehiclemodel) {
			$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
			$modelyear_url = "https://api.wheel-size.com/v2/years/?user_key=".$wheelApiKey."&make=".$make."&model=".$vehiclemodel. "&region=medm";
			$modelyear = file_get_contents($modelyear_url);
			$modelyear =json_decode($modelyear);
            $yearSelectHtml    = '<option value="">' . __('Select Year') . '</option>';
			if ($modelyear && !empty($modelyear->data) && count($modelyear->data) > 0) {
				foreach ($modelyear->data as $yearOption) {
					$yearSelectHtml .= '<option value="' . $yearOption->slug . '">' . $yearOption->name . '</option>';
				}
			}
			
            $response[] = [
                'vehicleyear' => $yearSelectHtml,
            ];

            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($response);

        }
    }
}
