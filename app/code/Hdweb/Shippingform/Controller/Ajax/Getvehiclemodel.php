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
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cartModel = $cartModel;
        $this->customerRepository = $customerRepository;
        $this->finderhelper = $finderhelper;
    }

    public function execute()
    {
        $vehiclemakes = $this->helper->jsonDecode($this->getRequest()->getContent());
        $response = [[
            'vehiclemodel' => '<option value="">' . __('Select Model') . '</option>',
            'vehicleyear' => '<option value="">' . __('Select Year') . '</option>',
        ]];

        if (!$vehiclemakes) {
            return $this->resultJsonFactory->create()->setData($response);
        }

        try {
            $wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
            $vehicleModelUrl = 'https://api.wheel-size.com/v2/models/?user_key='
                . $wheelApiKey
                . '&make='
                . urlencode((string) $vehiclemakes)
                . '&region=medm';
            $wheelVehicleModel = json_decode((string) file_get_contents($vehicleModelUrl));

            $modelSelectHtml = '<option value="">' . __('Select Model') . '</option>';
            $yearSelectHtml = '<option value="">' . __('Select Year') . '</option>';

            if ($wheelVehicleModel && !empty($wheelVehicleModel->data)) {
                foreach ($wheelVehicleModel->data as $modelOption) {
                    $modelSelectHtml .= '<option value="' . htmlspecialchars($modelOption->slug, ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($modelOption->name, ENT_QUOTES, 'UTF-8') . '</option>';
                }
            }

            $response[] = [
                'vehiclemodel' => $modelSelectHtml,
                'vehicleyear' => $yearSelectHtml,
            ];
        } catch (\Throwable $exception) {
            // Return placeholder options when the lookup fails.
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
