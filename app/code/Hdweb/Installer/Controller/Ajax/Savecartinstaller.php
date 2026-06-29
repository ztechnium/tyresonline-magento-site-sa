<?php

declare(strict_types=1);

namespace Hdweb\Installer\Controller\Ajax;

use Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class Savecartinstaller extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly CollectionFactory $collectionFactory,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        try {
            $content = (string) $this->getRequest()->getContent();
            $postParam = $content !== '' ? (array) $this->json->unserialize($content) : [];
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error('savecartinstaller: invalid JSON payload', ['exception' => $exception]);

            return $this->jsonResponse(['message' => 'fail', 'error' => 'invalid_payload']);
        }

        try {
            if (isset($postParam['pickup_store'], $postParam['pickup_date'], $postParam['pickup_time'])) {
                return $this->saveInstallerSelection($postParam);
            }

            if (isset($postParam['installer_id']) && (int) $postParam['installer_id'] === 0) {
                return $this->clearInstallerSelection();
            }
        } catch (\Throwable $exception) {
            $this->logger->error('savecartinstaller: save failed', ['exception' => $exception]);

            return $this->jsonResponse(['message' => 'fail', 'error' => 'server_error']);
        }

        return $this->jsonResponse(['message' => 'fail', 'error' => 'missing_fields']);
    }

    private function saveInstallerSelection(array $postParam)
    {
        $pickupStore = trim((string) $postParam['pickup_store']);
        $pickupDate = trim((string) $postParam['pickup_date']);
        $pickupTime = trim((string) $postParam['pickup_time']);

        if ($pickupStore === '' || $pickupDate === '' || $pickupTime === '') {
            return $this->jsonResponse(['message' => 'fail', 'error' => 'missing_fields']);
        }

        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter();
        $collection->addFieldToFilter('stores_id', (int) $pickupStore);

        $store = $collection->getFirstItem();
        if (!$store->getId()) {
            return $this->jsonResponse(['message' => 'fail', 'error' => 'installer_not_found']);
        }

        $quote = $this->checkoutSession->getQuote();
        $quote->setPickupDate($pickupDate);
        $quote->setPickupTime($pickupTime);
        $quote->setPickupStore($pickupStore);
        $quote->setDeliveryDate($pickupDate);
        $quote->setDeliveryComment($pickupTime);
        $quote->save();

        $this->checkoutSession->setPickupdate($pickupDate);
        $this->checkoutSession->setPickuptime($pickupTime);
        $this->checkoutSession->setPickupstoreid($pickupStore);

        $storeData = $store->getData();

        return $this->jsonResponse([
            'message' => 'success',
            'name' => $storeData['name'] ?? '',
            'name_rtl' => $storeData['name_rtl'] ?? ($storeData['name'] ?? ''),
            'address' => $storeData['address'] ?? '',
            'address_rtl' => $storeData['address_rtl'] ?? ($storeData['address'] ?? ''),
            'installer_lat' => $storeData['latitude'] ?? '',
            'installer_lng' => $storeData['longitude'] ?? '',
            'pickup_service' => $storeData['pickup_service'] ?? 0,
            'installer_date' => $pickupDate,
            'installer_time' => $pickupTime,
        ]);
    }

    private function clearInstallerSelection()
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->setPickupDate('');
        $quote->setPickupTime('');
        $quote->setPickupStore('');
        $quote->setDeliveryDate('');
        $quote->setDeliveryComment('');
        $quote->save();

        $this->checkoutSession->unsetPickupdate();
        $this->checkoutSession->unsetPickuptime();
        $this->checkoutSession->unsetPickupstoreid();

        return $this->jsonResponse(['message' => 'success']);
    }

    private function jsonResponse(array $data)
    {
        return $this->resultJsonFactory->create()->setData($data);
    }
}
