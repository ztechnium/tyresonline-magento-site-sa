<?php
namespace Hdweb\Coreoverride\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigVars implements ConfigProviderInterface
{
	protected $objectManager;
	protected $scopeConfig;

	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	){
		$this->objectManager	= $objectManager;
		$this->scopeConfig 		= $scopeConfig;
	}
   public function getConfig()
   {
	    $storeScope       = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$tabbyinstallment = $this->scopeConfig->getValue('productsearch/general/tabbyinstallment', $storeScope);
		$tabbyfourday     = $this->scopeConfig->getValue('productsearch/general/tabbyfourday', $storeScope);
		$additionalVariables['tabbymaxinstallmentprice'] = $tabbyinstallment;
		$additionalVariables['tabbyfourday']             = $tabbyfourday;
	   return $additionalVariables;
   }
}