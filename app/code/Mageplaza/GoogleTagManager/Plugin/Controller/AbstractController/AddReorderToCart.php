<?php

namespace Mageplaza\GoogleTagManager\Plugin\Controller\AbstractController;

use Exception;
use Magento\Framework\Registry;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Controller\AbstractController\Reorder;
use Magento\Framework\Message\ManagerInterface;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class AddReorderToCart
 * @package Mageplaza\GoogleTagManager\Plugin\Controller\AbstractController
 */
class AddReorderToCart
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @param Registry $coreRegistry
     * @param OrderFactory $orderFactory
     * @param ManagerInterface $messageManager
     * @param HelperData $helperData
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        Registry $coreRegistry,
        OrderFactory $orderFactory,
        ManagerInterface $messageManager,
        HelperData $helperData,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $categoryCollection
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->helperData = $helperData;
        $this->productRepository  = $productRepository;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @param Reorder $subject
     * @param $result
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(Reorder $subject, $result){
        $order = $this->coreRegistry->registry('current_order');

        if($order == null
            || !($this->helperData->isEnabled()
                && $this->helperData->isEnabledGTMGa4()
                && in_array(Event::ADD_TO_CART, $this->helperData->getShowEvents()))
        ){
            return $result;
        }

        try {
            $orderFactory = $this->orderFactory->create()->loadByIncrementIdAndStoreId($order->getIncrementId(), $order->getStoreId());
            [$measurementId, $secretAPI] = $this->helperData->getGa4TagIdAndSecretAPI();
            $i          = 0;

            foreach ($measurementId as $id){
                if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                    continue;
                }

                $sessionId = $this->helperData->getSessionId($measurementId[$i]);
                $payload   = json_encode($this->getBodyData($orderFactory->getItemsCollection(), $sessionId));

                $url = $this->helperData->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                $this->helperData->measureProtocolGA4($url, $payload);

                $i++;

                if ($i == count($measurementId) - 1) {
                    break;
                }
            }

        }catch (Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $result;
    }

    /**
     * @param $collection
     * @param $sessionId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getBodyData($collection, $sessionId){
        $values     = 0;
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                      = $this->helperData->getClientId();
        $resultGa4["events"]["name"]                 = "add_to_cart";
        $resultGa4["events"]["params"]["currency"]   = $this->helperData->getCurrentCurrency();
        $resultGa4["events"]["params"]["session_id"] = $sessionId;

        foreach ($collection as $item) {
            $product     = $this->productRepository->getById($item->getProductId());
            $values      += $item->getQty() * $this->helperData->convertPrice($product->getPrice());
            $categoryIds = $product->getCategoryIds();
            $categories  = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id',
                $categoryIds);

            $itemsArray[$i]["item_id"]     = $product->getSku();
            $itemsArray[$i]["item_name"]   = $product->getName();
            $itemsArray[$i]["affiliation"] = $this->helperData->getAffiliationName();
            $itemsArray[$i]["currency"]    = $this->helperData->getCurrentCurrency();
            $itemsArray[$i]["price"]       = abs($this->helperData->convertPrice($product->getPrice() ?: 0));
            $itemsArray[$i]["quantity"]    = abs($item->getQtyOrdered());

            if (!empty($categories)) {
                $j = null;
                foreach ($categories as $cat) {
                    $key                  = "item_category" . $j;
                    $j                    = (int) $j;
                    $itemsArray[$i][$key] = $cat->getName();
                    $j++;
                }
            }

            $productGa4[] = $itemsArray[$i];
        }

        $resultGa4["events"]["params"]["value"]      = $values;
        $resultGa4["events"]["params"]["debug_mode"] = 1;
        $resultGa4["events"]["params"]["items"]      = $productGa4;

        return $resultGa4;
    }
}
