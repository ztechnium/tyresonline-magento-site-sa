<?php
/**
 * @copyright Copyright (c) 2018 www.chetu.com
 */

namespace Chetu\Ajaxcart\Block;


class Js extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'js/main.phtml';

    protected $_ajaxcartHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Chetu\Ajaxcart\Helper\Data $ajaxcartHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_ajaxcartHelper = $ajaxcartHelper;
        $this->formKey = $formKey;
    }

    public function getAjaxCartInitOptions()
    {
        return $this->_ajaxcartHelper->getAjaxCartInitOptions();
    }

    public function getAjaxSidebarInitOptions()
    {
        $icon = $this->getViewFileUrl('images/loader-1.gif');
        return $this->_ajaxcartHelper->getAjaxSidebarInitOptions($icon);
    }
	
	    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    public function getAjaxLoginUrl()
    {
        return $this->getUrl('ajaxsuite/login');
    }

    public function getAjaxWishlistUrl()
    {
        return $this->getUrl('ajaxsuite/wishlist');
    }

    public function getAjaxCompareUrl()
    {
        return $this->getUrl('ajaxsuite/compare');
    }

    public function getAjaxSuiteInitOptions()
    {
        return $this->_ajaxcartHelper->getAjaxSuiteInitOptions();
    }

}