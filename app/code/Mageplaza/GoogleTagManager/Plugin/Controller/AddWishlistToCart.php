<?php

namespace Mageplaza\GoogleTagManager\Plugin\Controller;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Controller\Index\Cart;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Request\Http;

/**
 * Class AddWishlistToCart
 * @package Mageplaza\GoogleTagManager\Plugin\Controller
 */
class AddWishlistToCart
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @param ItemFactory $itemFactory
     * @param WishlistProviderInterface $wishlistProvider
     * @param HelperData $helperData
     * @param ProductRepositoryInterface $productRepository
     * @param ManagerInterface $messageManager
     * @param CollectionFactory $categoryCollection
     * @param Http $request
     */
    public function __construct(
        ItemFactory $itemFactory,
        WishlistProviderInterface $wishlistProvider,
        HelperData $helperData,
        ProductRepositoryInterface $productRepository,
        ManagerInterface $messageManager,
        CollectionFactory $categoryCollection,
        Http $request
    ) {
        $this->itemFactory        = $itemFactory;
        $this->wishlistProvider   = $wishlistProvider;
        $this->helperData         = $helperData;
        $this->productRepository  = $productRepository;
        $this->messageManager     = $messageManager;
        $this->categoryCollection = $categoryCollection;
        $this->request            = $request;
    }

    /**
     * @param Cart $result
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function beforeExecute(Cart $result)
    {
        $itemId   = (int)$this->request->getParam('item');
        /* @var $item \Magento\Wishlist\Model\Item */
        $item = $this->itemFactory->create()->load($itemId);
        $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId());

        if (!$wishlist
            || !($this->helperData->isEnabled()
                && $this->helperData->isEnabledGTMGa4()
                && in_array(Event::ADD_TO_CART, $this->helperData->getShowEvents()))
        ) {
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
                $payload   = json_encode($this->getBodyData($item->getProductId(), $sessionId));

                $url = $this->helperData->getUrlMeasureProtocolGA4($secretAPI[$i], $measurementId[$i]);

                $this->helperData->measureProtocolGA4($url, $payload);

                $i++;

                if ($i == count($measurementId) - 1) {
                    break;
                }
            }

            $this->messageManager->addSuccessMessage(__('Send add to cart data to Google Analytics 4 success'));
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBodyData($productId, $sessionId)
    {
        $itemsArray = [];
        $i          = 0;
        $resultGa4  = [];
        $productGa4 = [];

        $resultGa4["client_id"]                      = $this->helperData->getClientId();
        $resultGa4["events"]["name"]                 = "add_to_cart";
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
        $itemsArray[$i]["quantity"]    = abs($product->getQty());

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
