var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/place-order': {
                'Meetanshi_OrderUpload/js/place-order-with-comments-mixin': true
            }
        }
    },
    map: {
        '*': {
            meetanshiFileUpload: 'Meetanshi_OrderUpload/js/file-upload'
        }
    }
};
