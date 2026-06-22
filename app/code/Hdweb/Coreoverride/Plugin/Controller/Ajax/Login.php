<?php

namespace Hdweb\Coreoverride\Plugin\Controller\Ajax;

class Login
{
    public function aroundExecute(\Magento\Customer\Controller\Ajax\Login $subject, \Closure $proceed)
    {
        // your custom code before the original execute function
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $jsonHelper = $objectManager->get('Magento\Framework\Json\Helper\Data');
        $oilserviceHelper = $objectManager->get('Hdweb\Oilservice\Helper\Data');

        $postData = $jsonHelper->jsonDecode($subject->getRequest()->getContent());
        if(isset($postData['add_vehicle_make']) && isset($postData['add_vehicle_model']) && isset($postData['add_vehicle_engine'])){
            $vehicle_make = $postData['add_vehicle_make'];
            $vehicle_make_label = $postData['add_vehicle_make_label'];
            $vehicle_model = $postData['add_vehicle_model'];
            $vehicle_model_label = $postData['add_vehicle_model_label'];
            $vehicle_engine  = $postData['add_vehicle_engine'];
            $vehicle_engine_label = $postData['add_vehicle_engine_label'];
            if(!empty($vehicle_make) && !empty($vehicle_model) && !empty($vehicle_engine)){
                $oilgradeCollection = $objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
                                      ->addFieldToSelect('oil_litre')
                                      ->addFieldToSelect('mapping_id')
                                      ->addFieldToFilter('make_id', ['eq' => $vehicle_make])
                                      ->addFieldToFilter('model_id', ['eq' => $vehicle_model])
                                      ->addFieldToFilter('engine_id', ['eq' => $vehicle_engine])
                                      ->getFirstItem();
                $oilPerLitre = '';                    
                if(count($oilgradeCollection->getData()) > 0){
                    $oilPerLitre = $oilgradeCollection->getOilLitre();
                    $mappingId = $oilgradeCollection->getMappingId();
                }

                $vehicleArray = array('vehicle_make_id' => $vehicle_make, 'vehicle_make_label' => $vehicle_make_label, 'vehicle_model_id' => $vehicle_model, 'vehicle_model_label' => $vehicle_model_label, 'vehicle_engine_id' => $vehicle_engine, 'vehicle_engine_label' => $vehicle_engine_label, 'oil_per_litre' => $oilPerLitre, 'mapping_id' => $mappingId);
                $oilserviceHelper->createUserVehicleKey($vehicleArray);
            }
        }
        
        // call the original execute function
        $returnValue = $proceed();

        // your custom code after the original execute function
        
        return $returnValue;
    }
}