<?php
namespace Hdweb\Oilservice\Block;

class Oilservice extends \Magento\Framework\View\Element\Template 
{
	protected $_urlBuilder;
	protected $storeManager;
	protected $_resource;

    public function __construct(        
        \Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\App\ResourceConnection $resource,
        array $data = [] 
    ){        
		$this->_urlBuilder = $context->getUrlBuilder();
		$this->storeManager = $context->getStoreManager();
		$this->_resource = $resource;
		parent::__construct($context,$data);
    }
}