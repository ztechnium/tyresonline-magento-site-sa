<?php

namespace Mageplaza\BannerSlider\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\BannerSlider\Model\BannerFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SpecialOffers extends Template
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
        $this->pageConfig->getTitle()->set(__('Special Offers – TyreKing'));
        $this->pageConfig->setKeywords(__('Special Offers meta keywords'));
        $this->pageConfig->setDescription(__('Special Offers meta description'));
        $this->pageConfig->setMetaTitle('Special Offers meta title');

        return parent::_prepareLayout();
    }

    public function getBannerCollection()
    {
        $date = new \DateTime();
        $currentDate = $date->format('Y-m-d');
        $bannerCollection = $this->_bannerFactory->create()->getCollection();
        $bannerCollection->addFieldToFilter('status', 1);
        $bannerCollection->getSelect()
            ->where('from_date is null OR from_date <= ?', $currentDate)
            ->where('to_date is null OR to_date >= ?', $currentDate);
        $bannerCollection->setOrder('sort_order', 'ASC');

        return $bannerCollection;
    }

    public function getOfferCollectionFromCategory($category)
    {
        $date = new \DateTime();
        $currentDate = $date->format('Y-m-d');
        $bannerCollection = $this->_bannerFactory->create()->getCollection();
        $bannerCollection->addFieldToFilter('category', $category);
        $bannerCollection->addFieldToFilter('status', 1);
        $bannerCollection->getSelect()
            ->where('from_date is null OR from_date <= ?', $currentDate)
            ->where('to_date is null OR to_date >= ?', $currentDate);
        $bannerCollection->setOrder('sort_order', 'ASC');

        return $bannerCollection;
    }
}
