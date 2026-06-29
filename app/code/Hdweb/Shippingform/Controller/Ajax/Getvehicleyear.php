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
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cartModel = $cartModel;
        $this->customerRepository = $customerRepository;
        $this->finderhelper = $finderhelper;
    }

    public function execute()
    {
        $vehiclemodel = $this->helper->jsonDecode($this->getRequest()->getContent());
        $response = [[
            'vehicleyear' => '<option value="">' . __('Select Year') . '</option>',
        ]];

        if (!is_array($vehiclemodel) || empty($vehiclemodel['model']) || empty($vehiclemodel['make'])) {
            return $this->resultJsonFactory->create()->setData($response);
        }

        try {
            $make = (string) $vehiclemodel['make'];
            $model = (string) $vehiclemodel['model'];
            $wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
            $modelyearUrl = 'https://api.wheel-size.com/v2/years/?user_key='
                . $wheelApiKey
                . '&make='
                . urlencode($make)
                . '&model='
                . urlencode($model)
                . '&region=medm';
            $modelyear = json_decode((string) file_get_contents($modelyearUrl));
            $yearSelectHtml = '<option value="">' . __('Select Year') . '</option>';

            if ($modelyear && !empty($modelyear->data)) {
                foreach ($modelyear->data as $yearOption) {
                    $yearSelectHtml .= '<option value="' . htmlspecialchars($yearOption->slug, ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($yearOption->name, ENT_QUOTES, 'UTF-8') . '</option>';
                }
            }

            $response[] = [
                'vehicleyear' => $yearSelectHtml,
            ];
        } catch (\Throwable $exception) {
            // Return placeholder options when the lookup fails.
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
