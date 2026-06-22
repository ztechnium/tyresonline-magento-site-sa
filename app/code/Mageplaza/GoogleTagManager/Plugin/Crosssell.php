<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Plugin;

use Magento\Checkout\Block\Cart\Crosssell as CartCrosssell;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Block\Tag\ManagerTag;

/**
 * Class Crosssell
 * @package Mageplaza\GoogleTagManager\Plugin
 */
class Crosssell
{
    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * Crosssell constructor.
     *
     * @param RequestInterface $_request
     * @param Data $_helperData
     */
    public function __construct(
        RequestInterface $_request,
        Data $_helperData
    ) {
        $this->_request    = $_request;
        $this->_helperData = $_helperData;
    }

    /**
     * @param CartCrosssell $subject
     * @param $result
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterToHtml(
        CartCrosssell $subject,
        $result
    ) {
        if (!$this->_helperData->isEnabled()) {
            return $result;
        }

        $crossSellProducts = $subject->getItems();
        $data              = [];
        $dataGa            = '';
        $dataGa4           = '';
        $itemListId        = 'crosssell_products';
        $itemListName      = 'Cross-sell Products';
        $data              = $this->_helperData->getProductPositionData($crossSellProducts, $data, $itemListId,
            $itemListName);
        $trackPosition     = explode(
            ',',
            $this->_helperData->getConfigAnalytics4('track_position', $this->_helperData->getStoreId()) ?: ''
        );
        $dataCrossSell     = [];
        if ($this->_helperData->getConfigValue('checkout/cart/crosssell_enabled')
            && $this->_request->getFullActionName() === 'checkout_cart_index'
            && count($data)
            && in_array(ManagerTag::CROSSSELL, $trackPosition)
            && $this->_helperData->isEnabledGTMGa4()
        ) {
            $dataCrossSell = [
                'event'     => 'view_item_list',
                'ecommerce' => [
                    'item_list_id'   => $itemListId,
                    'item_list_name' => $itemListName,
                    'currency'       => $this->_helperData->getCurrentCurrency(),
                    'items'          => $data
                ]
            ];
        }

        if ($this->_helperData->isEnabledGTMGa4()) {
            $dataCrossSell['ga4_event'] = 'view_item_list';
            $dataGa4                    = $this->scriptHTML($dataCrossSell);
        }

        if ($this->_helperData->isEnabled()
            && $this->_helperData->getConfigAnalytics('enabled', $this->_helperData->getStoreId())
            && $this->_helperData->isUserNotAllowSaveCookieGa()) {
            $dataGa = $this->scriptHTMLGa($dataCrossSell);
        }

        if (!$this->_helperData->isEnabledGTMGa4() || !$this->_helperData->isUserNotAllowSaveCookieGa()) {
            return $result . $this->scriptHTML($dataCrossSell);
        }  else {
            return $result . $dataGa4 . $dataGa;
        }
    }

    /**
     * @param $dataCrossSell
     *
     * @return string
     */
    protected function scriptHTML($dataCrossSell)
    {
        $script = '';
        if ($dataCrossSell) {
            $script = '<!--Google Analytics 4 Added by Mageplaza GTM -->
                <script>
                dataLayer.push('
                . /** @noEscape */ Data::jsonEncode($dataCrossSell)
                . ');'
                . '</script>';

        }

        return $script;
    }

    /**
     * @param $dataCrossSell
     *
     * @return string
     */
    protected function scriptHTMLGa($dataCrossSell)
    {
        $script = '';
        if ($dataCrossSell) {
            $script = '<!--Analytics Added by Mageplaza GTM -->
                 <script>
                    gtag('
                . /** @noEscape */ Data::jsonEncode($dataCrossSell)
                . ');'
                . '</script>';

        }

        return $script;
    }
}
