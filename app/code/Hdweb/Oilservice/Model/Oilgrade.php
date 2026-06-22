<?php

namespace Hdweb\Oilservice\Model;

use Magento\Framework\Model\AbstractModel;

class Oilgrade extends AbstractModel
{
	protected function _construct()
	{
		$this->_init('Hdweb\Oilservice\Model\ResourceModel\Oilgrade');
	}
}