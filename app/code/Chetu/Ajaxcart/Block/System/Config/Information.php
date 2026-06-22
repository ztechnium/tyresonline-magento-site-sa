<?php
/**
 * @copyright Copyright (c) 2018 www.chetu.com
 */

namespace Chetu\Ajaxcart\Block\System\Config;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Config\Block\System\Config\Form\Field
     */
    protected $_fieldRenderer;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\ModuleResource
     */
    private $moduleResource;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ModuleResource $moduleResource,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $html .= $this->_getInfo();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getInfo()
    {
        $html = '<div class="support-info">';
        $html .= '  <h3>Support Policy</h3>';
        $html .= '  <p>Chetu provides 3-month free support for all of our extensions. We are not responsible for any bug or issue caused by your changes to our products. To report a bug, please send your email to: <a href="mailto:support@chetu.com" title="Chetu Support" target="_top">support@chetu.com</a></p>';
        $html .= '  <h3>Chetu\'s Blog</h3>';
        $html .= '  <p>We will be updating this blog on a regular basis to include you in new thinking and ideas emerging at Chetu, as well as to keep you updated with what’s going on in the e-commerce world. The blog is full with industry news, tutorials, hot releases, updates, promotions and so on. Let’s visit our blog to be kept updated!</p>';
        $html .= '  <h3>Follow Us</h3>';
        $html .= '  <div class="chetu-follow"><ul><li class="facebook"><a href="http://www.facebook.com/ChetuInc" title="Facebook" target="_blank"><img src="' . $this->getViewFileUrl('Chetu_Ajaxcart::images/facebook.png') . '" alt="Facebook"/></a></li><li class="twitter"><a href="https://twitter.com/ChetuInc" title="Twitter" target="_blank"><img src="' . $this->getViewFileUrl('Chetu_Ajaxcart::images/twitter.png') . '" alt="Twitter"/></a></li></ul></div>';
        $html .= '</div>';

        return $html;
    }
}