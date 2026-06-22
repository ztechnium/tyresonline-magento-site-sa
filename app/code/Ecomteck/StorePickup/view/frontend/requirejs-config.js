/**
 * Ecomteck_StorePickup Magento Extension
 *
 * @category    Ecomteck
 * @package     Ecomteck_StorePickup
 * @author      Ecomteck <ecomteck@gmail.com>
 * @website    http://www.ecomteck.com
 */

var config = {
    map: {
       '*': {
           'ecomteck/map-loader' : 'Ecomteck_StorePickup/js/map-loader',
           'ecomteck/stores-provider' : 'Ecomteck_StorePickup/js/model/stores-provider',
           'ecomteck/map' : 'Ecomteck_StorePickup/js/view/map',
           'Magento_Checkout/js/model/shipping-save-processor/default': 'Ecomteck_StorePickup/js/model/shipping-save-processor/default'
       }
    },
    config: {
    	mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Ecomteck_StorePickup/js/view/plugin/shipping': true
            }
        }
    }
};
