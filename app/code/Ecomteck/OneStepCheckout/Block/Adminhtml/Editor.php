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
namespace Ecomteck\OneStepCheckout\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;

class Editor extends Field
{
    /**
     * @var WysiwygConfig
     */
    private $wysiwygConfig;

    /**
     * @param Context $context
     * @param WysiwygConfig $wysiwygConfig
     * @param array $data
     */
    public function __construct(Context $context, WysiwygConfig $wysiwygConfig, array $data = [])
    {
        parent::__construct($context, $data);
        $this->wysiwygConfig = $wysiwygConfig;
    }

    /**
     * Enable wysiwyg editor for the element.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->addData([
            'wysiwyg' => true,
            'config'  => $this->wysiwygConfig->getConfig($element)
        ]);
        return parent::_getElementHtml($element);
    }
}
