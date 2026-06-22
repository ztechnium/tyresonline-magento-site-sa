<?php

namespace Mageplaza\GoogleTagManager\Plugin\Controller;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Controller\Index\Fromcart;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class AddBackFromCartToWishlist
 * @package Mageplaza\GoogleTagManager\Plugin\Controller
 */
class AddBackFromCartToWishlist
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var CheckoutCart
     */
    protected $cart;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @param Http $request
     * @param CheckoutCart $cart
     * @param HelperData $helperData
     * @param ManagerInterface $messageManager
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        Http $request,
        CheckoutCart $cart,
        HelperData $helperData,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $categoryCollection
    ) {
        $this->request            = $request;
        $this->cart               = $cart;
        $this->helperData         = $helperData;
        $this->messageManager     = $messageManager;
        $this->productRepository  = $productRepository;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @param Fromcart $result
     *
     * @return Fromcart
     * @throws NoSuchEntityException
     */
    public function beforeExecute(Fromcart $result)
    {
        $itemId = (int) $this->request->getParam('item');
        $item   = $this->cart->getQuote()->getItemById($itemId);
        if (!$item
            || (!$this->helperData->isEnabledGTMGa4()
                && !in_array(Event::ADD_TO_CART, $this->helperData->getShowEvents()))) {
            return $result;
        }

        try {
            [$measurementId, $secretAPI] = $this->helperData->getGa4TagIdAndSecretAPI();
            $i = 0;

            foreach ($measurementId as $id) {
                if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                    continue;
                }

                $sessionId = $this->helperData->getSessionId($measurementId[$i]);
                $payload   = json_encode($this->getBodyData($item->getProductId(), $sessionId,
                    $item->getBuyRequest()->getQty()));

                $url = $this->helperData->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                $this->helperData->measureProtocolGA4($url, $payload);

                $i++;

                if ($i == count($measurementId) - 1) {
                    break;
                }
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $result;
    }

    /**
     * @param $productId
     * @param $sessionId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getBodyData($productId, $sessionId, $qty)
    {
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                      = $this->helperData->getClientId();
        $resultGa4["events"]["name"]                 = "add_to_wishlist";
        $resultGa4["events"]["params"]["currency"]   = $this->helperData->getCurrentCurrency();
        $resultGa4["events"]["params"]["session_id"] = $sessionId;

        $product     = $this->productRepository->getById($productId);
        $categoryIds = $product->getCategoryIds();
        $categories  = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id',
            $categoryIds);

        $itemsArray[$i]["item_id"]     = $product->getSku();
        $itemsArray[$i]["item_name"]   = $product->getName();
        $itemsArray[$i]["affiliation"] = $this->helperData->getAffiliationName();
        $itemsArray[$i]["currency"]    = $this->helperData->getCurrentCurrency();
        $itemsArray[$i]["price"]       = abs($this->helperData->convertPrice($product->getPrice() ?: 0));
        $itemsArray[$i]["quantity"]    = $qty;

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

        $resultGa4["events"]["params"]["value"]      = $product->getQty() * $this->helperData->convertPrice($product->getPrice());
        $resultGa4["events"]["params"]["debug_mode"] = 1;
        $resultGa4["events"]["params"]["items"]      = $productGa4;

        return $resultGa4;
    }
}
