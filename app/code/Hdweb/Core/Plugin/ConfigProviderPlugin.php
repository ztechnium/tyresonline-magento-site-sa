<?php

namespace Hdweb\Core\Plugin;

class ConfigProviderPlugin
{

    protected $_checkoutSession;
    protected $storescollection;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Ecomteck\StoreLocator\Model\ResourceModel\Stores\Collection $storescollection
    ) {

        $this->_checkoutSession = $checkoutSession;
        $this->storescollection = $storescollection;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        $date   = $this->_checkoutSession->getPickupdate();
        $time   = $this->_checkoutSession->getPickuptime();
        $storid = $this->_checkoutSession->getPickupstoreid();
        if (!empty($date) && !empty($time) && !empty($storid)) {
            $stores = $this->storescollection->addFieldToFilter('status', 1)->addFieldToFilter('stores_id', $storid);
            if (count($stores) > 0) {
                $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $storeManager = $_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                $storeCode = $storeManager->getStore()->getCode();
                if($storeCode == 'en'){
                    $storename                     = $stores->getFirstItem()->getName();    
                } else {
                    $storename                     = $stores->getFirstItem()->getNameRtl();
                }
                
                $result['storepickup']['date'] = $date;
                $result['storepickup']['time'] = $time;
                $result['storepickup']['id']   = $storid;
                $result['storepickup']['name'] = $storename;

                $stores                                 = $stores->getFirstItem();
                $data                                   = $stores->getData();
                $data['lat']                            = $data['latitude'];
                $data['lng']                            = $data['longitude'];
                $data['image']                          = $stores->getImageUrl();
                $data['details_image']                  = $stores->getDetailsImageUrl();
                $data['opening_hours_formated']         = $stores->getOpeningHoursFormated();
                $data['opening_hours']                  = $stores->getOpeningHoursConfig();
                $data['special_opening_hours']          = $stores->getSpecialOpeningHoursConfig();
                $data['special_opening_hours_formated'] = $stores->getSpecialOpeningHoursFormated();
                $data['details_link']                   = $stores->getStoresUrl();
                $data['country']                        = $stores->getCountryName();
                unset($data['distance']);
                if (!$data['country']) {
                    $data['country'] = '';
                }
                if (!$data['city']) {
                    $data['city'] = '';
                }

                $selectedStoredataJson = json_encode($data);

                $result['storepickup']['json'] = $selectedStoredataJson;
            } else {
                $result['storepickup'] = "undefined";
            }
        } else {
            $result['storepickup'] = "undefined";
        }

        return $result;
    }

}
