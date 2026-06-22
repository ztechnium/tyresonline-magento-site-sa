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
namespace Ecomteck\OneStepCheckout\Plugin;

use Magento\Tax\Model\TaxConfigProvider;
use Ecomteck\OneStepCheckout\Service\GeoService;

class GeoIpCountryPlugin
{
    /**
     * @var GeoService
     */
    private $geoService;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;

    /**
     * @param GeoService $geoService
     */
    public function __construct(
        GeoService $geoService,
        \Ecomteck\OneStepCheckout\Helper\Config $config
    ) {
        $this->geoService = $geoService;
        $this->_config = $config;
    }

    /**
     * @param TaxConfigProvider $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetConfig(TaxConfigProvider $subject, callable $proceed)
    {
        $config = $proceed();
        if (!$this->_config->isEnabled() || !$this->_config->isGeoIpEnabled()) {
            return $config;
        }
        $country = $this->geoService->getCountry();
        if ($country !== null) {
            $config['defaultCountryId'] = $country;
        }
        return $config;
    }
}
