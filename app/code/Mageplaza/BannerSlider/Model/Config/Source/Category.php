<?php

/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_BannerSlider
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\BannerSlider\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Mageplaza\BannerSlider\Helper\Data as bannerHelper;

/**
 * Class Type
 * @package Mageplaza\BannerSlider\Model\Config\Source
 */
class Category implements ArrayInterface
{
    /**
     * @var Data
     */
    protected $bannerHelper;

    /**
     * Category constructor.
     * @param bannerHelper $helperData
     */
    public function __construct(bannerHelper $bannerHelper)
    {
        $this->bannerHelper = $bannerHelper;
    }

    /**
     * to option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->bannerHelper->getCategories();

        return $options;
    }
}
