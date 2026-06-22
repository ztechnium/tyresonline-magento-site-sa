<?php
/**
 * @copyright Copyright (c) 2018 www.chetu.com
 */

namespace Chetu\Ajaxcart\Block\Product;

class ConfigurableOption extends \Magento\Framework\View\Element\Template
{

    public function getColorLabel()
    {
        return $this->_request->getParam('colorLabel');
    }

    public function getSizeLabel()
    {
        return $this->_request->getParam('sizeLabel');
    }
}