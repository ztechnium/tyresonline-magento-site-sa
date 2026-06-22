<?php

namespace Mageplaza\GoogleTagManager\Plugin\Controller;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Controller\Cart\Addgroup;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Framework\Message\ManagerInterface;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddRecentlyOrderToCart
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param Http $request
     * @param ObjectManagerInterface $objectManager
     * @param ManagerInterface $messageManager
     * @param HelperData $helperData
     * @param CollectionFactory $categoryCollection
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Http $request,
        ObjectManagerInterface $objectManager,
        ManagerInterface $messageManager,
        HelperData $helperData,
        CollectionFactory $categoryCollection,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->request = $request;
        $this->objectManager = $objectManager;
        $this->messageManager = $messageManager;
        $this->helperData = $helperData;
        $this->categoryCollection = $categoryCollection;
        $this->productRepository  = $productRepository;
    }

    /**
     * @param Addgroup $result
     *
     * @return Addgroup
     * @throws NoSuchEntityException
     */
    public function beforeExecute(Addgroup $result){
        $orderItemIds = $this->request->getPost('order_items');

        if(!is_array($orderItemIds)
            || (!$this->helperData->isEnabledGTMGa4()
            && !in_array(Event::ADD_TO_CART, $this->helperData->getShowEvents()))){
            return $result;
        }

        try {
            /* @var OrderItemCollection $itemsCollection*/
            $itemsCollection = $this->objectManager->create(OrderItem::class)
                ->getCollection()
                ->addIdFilter($orderItemIds)
                ->load();
            [$measurementId, $secretAPI] = $this->helperData->getGa4TagIdAndSecretAPI();

            $i = 0;
            foreach ($measurementId as $id) {
                if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                    continue;
                }

                $sessionId = $this->helperData->getSessionId($measurementId[$i]);
                $payload   = json_encode($this->getBodyData($itemsCollection, $sessionId));

                $url = $this->helperData->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                $this->helperData->measureProtocolGA4($url, $payload);

                $i++;

                if ($i == count($measurementId) - 1) {
                    break;
                }
            }

            $this->messageManager->addSuccessMessage(__('Send add to cart data to Google Analytics 4 success'));
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBodyData($collection, $sessionId){
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
            $values      += $this->helperData->convertPrice($product->getPrice());
            $categoryIds = $product->getCategoryIds();
            $categories  = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id',
                $categoryIds);

            $itemsArray[$i]["item_id"]     = $product->getSku();
            $itemsArray[$i]["item_name"]   = $product->getName();
            $itemsArray[$i]["affiliation"] = $this->helperData->getAffiliationName();
            $itemsArray[$i]["currency"]    = $this->helperData->getCurrentCurrency();
            $itemsArray[$i]["price"]       = abs($this->helperData->convertPrice($product->getPrice()));
            $itemsArray[$i]["quantity"]    = 1;

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
