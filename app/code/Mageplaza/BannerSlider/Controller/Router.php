<?php

namespace Mageplaza\BannerSlider\Controller;

use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;
use Mageplaza\BannerSlider\Model\Banner;
use Mageplaza\BannerSlider\Helper\Data as bannerHelper;

class Router implements RouterInterface
{
    protected $actionFactory;
    protected $eventManager;
    protected $response;
    protected $dispatched;
    protected $storeManager;
    protected $bannerModel;
    protected $bannerHelper;


    public function __construct(
        ActionFactory $actionFactory,
        ResponseInterface $response,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Banner $bannerModel,
        bannerHelper $bannerHelper
    ) {

        $this->actionFactory = $actionFactory;
        $this->response = $response;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->bannerModel = $bannerModel;
        $this->bannerHelper = $bannerHelper;
    }

    public function match(RequestInterface $request)
    {
        if (!$this->dispatched) {
            $urlKey = trim($request->getPathInfo(), '/');

            $origUrlKey = $urlKey;
            $condition = new DataObject(['url_key' => $urlKey, 'continue' => true]);

            if ($condition->getRedirectUrl()) {
                $this->response->setRedirect($condition->getRedirectUrl());
                $request->setDispatched(true);

                return $this->actionFactory->create(
                    'Magento\Framework\App\Action\Redirect',
                    ['request' => $request]
                );
            }
            if (!$condition->getContinue()) {
                return null;
            }

            if ($urlKey == 'special-offers') {
                $request->setModuleName('mpbannerslider')
                    ->setControllerName('specialoffers')
                    ->setActionName('index') //;
                    ->setParam('route', $urlKey);
                $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                $this->dispatched = true;
                return $this->actionFactory->create(
                    'Magento\Framework\App\Action\Forward',
                    ['request' => $request]
                );
            }
            $identifiers = explode('/', $urlKey);
            if ($identifiers[0] == 'special-offers' && $identifiers[1] != 'ajax') {
                /* if (count($identifiers) == 2) {
                    $idBanner = '';
                    $isEnable = false;
                    $spcialOfferUrl = $identifiers[1];
                    $banner = $this->bannerModel->getCollection()
                        ->addFieldToFilter('status', array('eq' => 1))
                        ->addFieldToFilter('url_banner', array('eq' => $spcialOfferUrl))
                        ->getFirstItem();
                    $idBanner = $banner->getBannerId();
                    if (($banner && $banner->getBannerId())) {
                        $isEnable = true;
                    }
                    if ($isEnable == true) {
                        $request->setModuleName('mpbannerslider')
                            ->setControllerName('specialoffers')
                            ->setActionName('view')
                            ->setParam('banner_id', $idBanner);
                        $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                        $this->dispatched = true;
                        return $this->actionFactory->create(
                            'Magento\Framework\App\Action\Forward',
                            ['request' => $request]
                        );
                    }
                } */
                if (count($identifiers) == 2) {
                    $offerCategoryUrl = $identifiers[1];
                    $offerCategory = str_replace('-', ' ', $offerCategoryUrl);
                    $offerCategory = ucwords($offerCategory);
                    $offerCategoryOptions = $this->bannerHelper->getCategories();
                    $offerCategoryOptionsValues = array_column($offerCategoryOptions, 'value');
                    if (in_array($offerCategory, $offerCategoryOptionsValues)) {
                        $request->setModuleName('mpbannerslider')
                            ->setControllerName('specialoffers')
                            ->setActionName('category')
                            ->setParams(array(
                                'category' => $offerCategory,
                                'category_url' => $offerCategoryUrl,
                                'route' => $identifiers[0],
                            ));
                        $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                        $this->dispatched = true;
                        return $this->actionFactory->create(
                            'Magento\Framework\App\Action\Forward',
                            ['request' => $request]
                        );
                    }
                } elseif (count($identifiers) == 3) {
                    $idBanner = '';
                    $isEnable = false;
                    $offerCategoryUrl = $identifiers[1];
                    $offerCategory = str_replace('-', ' ', $offerCategoryUrl);
                    $offerCategory = ucwords($offerCategory);
                    $spcialOfferUrl = $identifiers[2];
                    $banner = $this->bannerModel->getCollection()
                        ->addFieldToFilter('status', array('eq' => 1))
                        ->addFieldToFilter('url_banner', array('eq' => $spcialOfferUrl))
                        ->addFieldToFilter('category', $offerCategory)
                        ->getFirstItem();
                    $idBanner = $banner->getBannerId();
                    if (($banner && $banner->getBannerId())) {
                        $isEnable = true;
                    }
                    if ($isEnable == true) {
                        $request->setModuleName('mpbannerslider')
                            ->setControllerName('specialoffers')
                            ->setActionName('view')
                            //->setParam('banner_id', $idBanner);
                            ->setParams(array(
                                'category' => $offerCategory,
                                'category_url' => $offerCategoryUrl,
                                'banner_id' => $idBanner,
                            ));
                        $request->setAlias(Url::REWRITE_REQUEST_PATH_ALIAS, $urlKey);
                        $this->dispatched = true;
                        return $this->actionFactory->create(
                            'Magento\Framework\App\Action\Forward',
                            ['request' => $request]
                        );
                    }
                }
            }
        }
    }
}
