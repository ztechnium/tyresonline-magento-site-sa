<?php
namespace Hdweb\Rfc\Block\Adminhtml\Rfcimport\Edit\Renderer;

class Samplefile extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
    * Get the after element html.
    *
    * @return mixed
    */
    public function getAfterElementHtml()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$currentStore = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
		$mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$csvPath = $mediaUrl.'RFC-Import-Sample.csv';
		return '<a href="' . $csvPath . '">' . $csvPath . '</a>';
    }


}