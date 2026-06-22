var config = {
    paths: {
            'bootstrap':'Magento_Theme/js/bootstrap.bundle.min',
            'lazysizes':'Magento_Theme/js/lazysizes.min',
            'owlcarousel':'Magento_Theme/js/owl.carousel.min',
            'izimodal':'Magento_Theme/js/iziModal.min',
            'select2':'Magento_Theme/js/select2.min',
            'scrollbar':'Magento_Theme/js/jquery.scrollbar.min',
            //'custom':'Magento_Theme/js/custom',
    } ,
    shim: {
        'bootstrap': {
            'deps': ['jquery']
        },
        'owlcarousel': {
            'deps': ['jquery']
        },
        'lazysizes': {
            'deps': ['jquery']
        },
        'izimodal': {
            'deps': ['jquery']
        },
        'select2': {
            'deps': ['jquery']
        },
        'scrollbar': {
            'deps': ['jquery']
        },
        /*'custom': {
            'deps': ['jquery']
        },*/
    }
};