<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
 
namespace Hdweb\Addattribute\Block\Product\ListProduct;
  
class Related extends \Magento\Catalog\Block\Product\ProductList\Related
{
	public function getIdentities()
    {
        $identities = [];

        if (is_array($this->getItems()) || is_object($this->getItems()))
        {
            foreach ($this->getItems() as $item)
            {
                $identities = array_merge($identities, $item->getIdentities());
            }
        }
        return $identities;

    }
}