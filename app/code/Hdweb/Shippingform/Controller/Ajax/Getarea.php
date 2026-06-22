<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hdweb\Shippingform\Controller\Ajax;

/*use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;*/

class Getarea extends \Magento\Framework\App\Action\Action
{

    protected $helper;
    protected $resultJsonFactory;
    protected $cartModel;
    protected $customerRepository;
    protected $corehelper;
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Hdweb\Core\Helper\Data $corehelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->helper              = $helper;
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->cartModel           = $cartModel;
        $this->customerRepository  = $customerRepository;
        $this->corehelper          = $corehelper;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $response = [[
            'area' => '<option value="">' . __('Select Area') . '</option>',
        ]];

        $selectedCity = (string) $this->getRequest()->getParam('city', '');
        if ($selectedCity === '') {
            $requestBody = (string) $this->getRequest()->getContent();
            if ($requestBody !== '') {
                try {
                    $decodedRequest = $this->helper->jsonDecode($requestBody);
                    if (is_string($decodedRequest)) {
                        $selectedCity = $decodedRequest;
                    } elseif (is_array($decodedRequest) && isset($decodedRequest['city'])) {
                        $selectedCity = (string) $decodedRequest['city'];
                    }
                } catch (\Throwable $exception) {
                    parse_str($requestBody, $parsedRequest);
                    if (isset($parsedRequest['city'])) {
                        $selectedCity = (string) $parsedRequest['city'];
                    }
                }
            }
        }

        if ($selectedCity) {
            try {
                $connection = $this->resourceConnection->getConnection();
                $tableName = $connection->getTableName('checkout_city_area');
                $select = $connection->select()
                    ->from($tableName, ['area'])
                    ->where('city = ?', $selectedCity)
                    ->order('area ASC');
                $result = $connection->fetchAll($select);
                $areaSelectHtml = '<option value="">' . __('Select Area') . '</option>';

                foreach ($result as $value) {
                    $areaSelectHtml .= '<option value="' . htmlspecialchars($value['area'], ENT_QUOTES, 'UTF-8') . '">'
                        . htmlspecialchars($value['area'], ENT_QUOTES, 'UTF-8') . '</option>';
                }

                $response[] = [
                            'area' => $areaSelectHtml,
                        ];
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData($response);
            } catch (\Throwable $ex) {
                return $this->resultJsonFactory->create()->setData($response);
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
