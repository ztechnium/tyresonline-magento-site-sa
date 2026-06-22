<?php

namespace Mageplaza\BannerSlider\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\BannerSlider\Model\Banner;

class SpecialOfferView extends Template
{
    protected $bannerModel;

    public function __construct(
        Context $context,
        Banner $bannerModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->bannerModel = $bannerModel;
    }

    protected function _prepareLayout()
    {
        $banner = $this->getBanner();
        $pageTitle = $banner->getMetaTitle();
        $metaDescription = $banner->getMetaDescription();
        $this->pageConfig->addBodyClass('offer-view');
        if ($pageTitle) {
            $this->pageConfig->getTitle()->set($pageTitle);
        }
        if ($metaDescription) {
            $this->pageConfig->setDescription($metaDescription);
        }

        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        $breadcrumbs->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Home'),
                'link' => $this->_storeManager->getStore()->getBaseUrl(),
            ]
        );
        $breadcrumbs->addCrumb(
            'special-offers',
            [
                'label' => __('Special Offers'),
                'title' => __('Special Offers'),
                'link' => $this->_storeManager->getStore()->getBaseUrl() . 'special-offers',
            ]
        );
        $categoryUrl = $this->getRequest()->getParam('category_url');
        $category = $this->getRequest()->getParam('category');
        if ($category && $categoryUrl) {
            $breadcrumbs->addCrumb(
                $categoryUrl,
                [
                    'label' => __($category),
                    'title' => __($category),
                    'link' => $this->_storeManager->getStore()->getBaseUrl() . 'special-offers/' . $categoryUrl,
                ]
            );
        }
        $breadcrumbs->addCrumb(
            $banner->getUrlBanner(),
            [
                'label' => __($banner->getName()),
                'title' => __($banner->getName()),
            ]
        );

        $this->pageConfig->getTitle()->set(__($banner->getMetaTitle()));
        $this->pageConfig->setDescription(__($banner->getMetaDescription()));
        $this->pageConfig->setMetaTitle($banner->getMetaTitle());

        return parent::_prepareLayout();
    }

    public function getBanner()
    {
        $bannerId = $this->getRequest()->getParam('banner_id');
        if ($bannerId) {
            $banner = $this->bannerModel->load($bannerId);
            return $banner;
        }
    }
}
