<?php

namespace Hdweb\Rfc\Model;

use Magento\Framework\Model\AbstractModel;

class Rfcmaster extends AbstractModel
{
	protected function _construct()
	{
		$this->_init('Hdweb\Rfc\Model\ResourceModel\Rfcmaster');
	}
}