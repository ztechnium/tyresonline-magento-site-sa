<?php

namespace Hdweb\Coreoverride\Plugin\Model\Method;

class MethodAvailable
{
    /**
     * @param Magento\Payment\Model\MethodList $subject
     * @param $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAvailableMethods(\Magento\Payment\Model\MethodList $subject, $result)
    {
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
		$isOfflineMethod = $customerSession->getOfflineMethod();
		$tabbyMethodAvailable = $objectManager->create('Hdweb\Installer\Helper\Data')->tabbyMethodAvailable(); //check tabby method
        foreach ($result as $key=>$_result) {
			/* if(isset($_COOKIE['tabbymethod_available'])) {
				if($_COOKIE['tabbymethod_available'] == 0){
					if ($_result->getCode() == "tabby_installments") {
						unset($result[$key]);
					}
				}
			} */
			if($tabbyMethodAvailable == 0){
				if ($_result->getCode() == "tabby_installments") {
					unset($result[$key]);
				}
			}
			if(!$isOfflineMethod) {
				if ($_result->getCode() == "cashondelivery") {
					unset($result[$key]);
				}
			}
        }
        return $result;
    }
}