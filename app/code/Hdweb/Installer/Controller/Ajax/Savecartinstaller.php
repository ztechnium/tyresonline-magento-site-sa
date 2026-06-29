<?php

namespace Hdweb\Installer\Controller\Ajax;

use Ecomteck\StoreLocator\Model\ResourceModel\Stores\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Savecartinstaller extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Json
     */
    private $json;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        CollectionFactory $collectionFactory,
        Json $json
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->collectionFactory = $collectionFactory;
        $this->json = $json;
    }

    public function execute()
    {
        $postParam = $this->json->unserialize($this->getRequest()->getContent());

        if (isset($postParam['pickup_store'], $postParam['pickup_date'], $postParam['pickup_time'])) {
            $pickupStore = $postParam['pickup_store'];
            $pickupDate = $postParam['pickup_date'];
            $pickupTime = $postParam['pickup_time'];

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

            $collection = $this->collectionFactory->create();
            $collection->addActiveFilter();
            $collection->addFieldToFilter('stores_id', (int) $pickupStore);

            if ($collection->getSize()) {
                $storeData = $collection->getFirstItem()->getData();

                return $this->resultJsonFactory->create()->setData([
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

            return $this->resultJsonFactory->create()->setData(['message' => 'fail']);
        }

        if (isset($postParam['installer_id']) && (int) $postParam['installer_id'] === 0) {
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

            return $this->resultJsonFactory->create()->setData(['message' => 'success']);
        }

        return $this->resultJsonFactory->create()->setData(['message' => 'fail']);
    }
}
