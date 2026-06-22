<?php

namespace Mageplaza\GoogleTagManager\Observer\Adminhtml;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Mageplaza\GoogleTagManager\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;

/**
 * Class CreditmemoRefundSaveAfter
 * @package Mageplaza\GoogleTagManager\Observer\Adminhtml
 */
class CreditmemoRefundSaveAfter implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ManagerInterface
     */
    protected $_manager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ManagerInterface $manager,
        Data $helper,
        Product $product,
        CollectionFactory $categoryCollection
    ) {
        $this->request            = $request;
        $this->_helper            = $helper;
        $this->_manager           = $manager;
        $this->storeManager       = $storeManager;
        $this->product            = $product;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        $canShowEvents = $this->_helper->getShowEvents();

        if ($this->_helper->isEnabled() &&
            $this->_helper->isEnabledGTMGa4() &&
            in_array(Event::ORDER_REFUND, $canShowEvents)) {
            /* @var $creditmemo Creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $order      = $creditmemo->getOrder();

            try {
                [$measurementId, $secretAPI] = $this->_helper->getGa4TagIdAndSecretAPI();
                $i = 0;
                foreach ($measurementId as $id) {
                    if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                        continue;
                    }

                    $sessionId = $this->_helper->getSessionId($measurementId[$i]);
                    $payload   = json_encode($this->getBodyData($creditmemo, $order, $sessionId));

                    $url = $this->_helper->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                    $this->_helper->measureProtocolGA4($url, $payload);

                    $i++;

                    if ($i == count($measurementId) - 1) {
                        break;
                    }
                }

                $this->_manager->addSuccessMessage(__('Send refund data to Google Analytics 4 success'));

            } catch (Exception $e) {
                $this->_manager->addErrorMessage($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * @param $creditmemo
     * @param $order
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getBodyData($creditmemo, $order, $sessionId)
    {
        $allItems   = $creditmemo->getAllItems();
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                          = $this->_helper->getClientId();
        $resultGa4["events"]["name"]                     = "refund";
        $resultGa4["events"]["params"]["currency"]       = $this->_helper->getCurrentCurrency();
        $resultGa4["events"]["params"]["transaction_id"] = $creditmemo->getIncrementId();
        $resultGa4["events"]["params"]["value"]          = $creditmemo->getGrandTotal();
        $resultGa4["events"]["params"]["coupon"]         = (string) $order->getCouponCode();
        $resultGa4["events"]["params"]["shipping"]       = $creditmemo->getShippingAmount();
        $resultGa4["events"]["params"]["tax"]            = $creditmemo->getTaxAmount();
        $resultGa4["events"]["params"]["session_id"]     = $sessionId;
        $resultGa4["events"]["params"]["debug_mode"]     = 1;

        foreach ($allItems as $item) {
            $i++;

            $product     = $this->product->load($item->getProductId());
            $categoryIds = $product->getCategoryIds();
            $categories  = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id',
                $categoryIds);

            $itemsArray[$i]["item_id"]     = $item->getSku();
            $itemsArray[$i]["item_name"]   = $item->getName();
            $itemsArray[$i]["affiliation"] = $this->_helper->getAffiliationName();
            $itemsArray[$i]["coupon"]      = (string) $order->getCouponCode();
            $itemsArray[$i]["currency"]    = $this->_helper->getCurrentCurrency();
            $itemsArray[$i]["discount"]    = abs($creditmemo->getDiscountAmount());
            $itemsArray[$i]["price"]       = abs($this->_helper->convertPrice($item->getPrice()));
            $itemsArray[$i]["quantity"]    = abs($item->getQty());

            if (!empty($categories)) {
                $j = 0;
                foreach ($categories as $cat) {
                    $key                  = "item_category" . $j;
                    $itemsArray[$i][$key] = $cat->getName();
                    $j++;
                }
            }

            $productGa4[] = $itemsArray[$i];
        }

        $resultGa4["events"]["params"]["items"] = $productGa4;

        return $resultGa4;
    }
}
