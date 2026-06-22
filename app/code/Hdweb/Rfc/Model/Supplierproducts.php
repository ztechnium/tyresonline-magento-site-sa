<?php

namespace Hdweb\Rfc\Model;

use Magento\Framework\Model\AbstractModel;

class Supplierproducts extends AbstractModel
{
	protected function _construct()
	{
		$this->_init('Hdweb\Rfc\Model\ResourceModel\Supplierproducts');
	}
}