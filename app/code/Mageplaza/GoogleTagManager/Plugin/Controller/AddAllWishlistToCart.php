<?php

namespace Mageplaza\GoogleTagManager\Plugin\Controller;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Controller\Index\Allcart;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class AddAllToCart
 * @package Mageplaza\GoogleTagManager\Plugin\Controller
 */
class AddAllWishlistToCart
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

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
     * @param WishlistProviderInterface $wishlistProvider
     * @param HelperData $helperData
     * @param ManagerInterface $messageManager
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        WishlistProviderInterface $wishlistProvider,
        HelperData $helperData,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $categoryCollection
    ) {
        $this->wishlistProvider   = $wishlistProvider;
        $this->helperData         = $helperData;
        $this->messageManager     = $messageManager;
        $this->productRepository  = $productRepository;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @param Allcart $subject
     * @param $result
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterExecute(Allcart $subject, $result)
    {
        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist
            || !($this->helperData->isEnabled()
                && $this->helperData->isEnabledGTMGa4()
                && in_array(Event::ADD_TO_CART, $this->helperData->getShowEvents()))
        ) {
            return $result;
        }

        try {
            [$measurementId, $secretAPI] = $this->helperData->getGa4TagIdAndSecretAPI();
            $i          = 0;
            $collection = $wishlist->getItemCollection()->setVisibilityFilter();

            foreach ($measurementId as $id) {
                if ($secretAPI[$i] == '' || !isset($secretAPI[$i])) {
                    continue;
                }

                $sessionId = $this->helperData->getSessionId($measurementId[$i]);
                $payload   = json_encode($this->getBodyData($collection, $sessionId));

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
     * @param $collection
     * @param $sessionId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getBodyData($collection, $sessionId)
    {
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
            $itemsArray[$i]["quantity"]    = abs($item->getQty());

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