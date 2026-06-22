<?php
/**
 * Ecomteck
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Ecomteck.com license that is
 * available through the world-wide-web at this URL:
 * https://ecomteck.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Ecomteck
 * @package     Ecomteck_OneStepCheckout
 * @copyright   Copyright (c) 2018 Ecomteck (https://ecomteck.com/)
 * @license     https://ecomteck.com/LICENSE.txt
 */
namespace Ecomteck\OneStepCheckout\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Locale\Currency;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config extends AbstractHelper
{
    const ENABLE = 'one_step_checkout/general/enable';
    const ROUTER_NAME = 'one_step_checkout/general/router_name';
    const ENABLE_GEO_IP = 'one_step_checkout/geo_ip/enabled';
    const REMOVE_HEADER = 'one_step_checkout/display/disable_header';
    const REMOVE_FOOTER = 'one_step_checkout/display/disable_footer';

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
    */
	protected $_filterProvider;

    public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Cms\Model\Template\FilterProvider $filterProvider
		) {
		parent::__construct($context);
		$this->_filterProvider  = $filterProvider;
    }
    
    public function filter($str, $storeId = '')
	{
		if($str){
			$filter = $this->_filterProvider->getPageFilter();
			$html   = $filter->filter($str);
			return $html;
		}
		return $str;
	}
    /**
     * Is module enabled
     *
     * @param null $storeId
     * @return string
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::ENABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get router name
     *
     * @param null $storeId
     * @return string
     */
    public function getRouterName($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::ROUTER_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get checkout url
     *
     * @param null $storeId
     * @return string
     */
    public function getCheckoutUrl($storeId = null)
    {
        return $this->getRouterName($storeId);
    }

    /**
     * Is module enabled
     *
     * @param null $storeId
     * @return string
     */
    public function isGeoIpEnabled($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::ENABLE_GEO_IP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is remove header enabled
     *
     * @param null $storeId
     * @return string
     */
    public function isRemoveHeader($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::REMOVE_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is remove fotter enabled
     *
     * @param null $storeId
     * @return string
     */
    public function isRemoveFooter($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::REMOVE_HEADER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
