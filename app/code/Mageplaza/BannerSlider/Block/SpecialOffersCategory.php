<?php

namespace Mageplaza\BannerSlider\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\BannerSlider\Model\BannerFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SpecialOffersCategory extends Template
{
    protected $_bannerFactory;
    protected $_date;

    public function __construct(
        Context $context,
        BannerFactory $bannerFactory,
        DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_bannerFactory = $bannerFactory;
        $this->_date = $date;
    }

    public function _prepareLayout()
    {
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
            'car-tyres',
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
                ]
            );
        }
        $this->pageConfig->getTitle()->set(__('Special Offers ' . $category . ' – TyreKing'));
        $this->pageConfig->setKeywords(__('Special Offers ' . $category . ' meta keywords'));
        $this->pageConfig->setDescription(__('Special Offers ' . $category . ' meta description'));
        $this->pageConfig->setMetaTitle('Special Offers ' . $category . ' meta title');

        return parent::_prepareLayout();
    }

    public function getBannerCollectionBKP()
    {
        $category = $this->getRequest()->getParam('category');
        $bannerCollection = $this->_bannerFactory->create()->getCollection();
        $bannerCollection->addFieldToFilter('category', $category);
        $bannerCollection->addFieldToFilter('status', 1);
        $bannerCollection->getSelect()
            ->where('from_date is null OR from_date <= ?', $this->_date->date())
            ->where('to_date is null OR to_date >= ?', $this->_date->date());
        $bannerCollection->setOrder('sort_order', 'ASC');

        return $bannerCollection;
    }

    public function getBannerCollection()
    {
        $bannerCollection = $this->_bannerFactory->create()->getCollection();
        $bannerCollection->addFieldToFilter('status', 1);
        $bannerCollection->getSelect()
            ->where('from_date is null OR from_date <= ?', $this->_date->date())
            ->where('to_date is null OR to_date >= ?', $this->_date->date());
        $bannerCollection->setOrder('sort_order', 'ASC');

        return $bannerCollection;
    }

    public function getOfferCollectionFromCategory($category)
    {
        $bannerCollection = $this->_bannerFactory->create()->getCollection();
        $bannerCollection->addFieldToFilter('category', $category);
        $bannerCollection->addFieldToFilter('status', 1);
        $bannerCollection->getSelect()
            ->where('from_date is null OR from_date <= ?', $this->_date->date())
            ->where('to_date is null OR to_date >= ?', $this->_date->date());
        $bannerCollection->setOrder('sort_order', 'ASC');

        return $bannerCollection;
    }
}
