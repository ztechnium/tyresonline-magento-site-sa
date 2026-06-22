var config = {
	"map": {
		"*": {
			"mlazyload": "MGS_Ajaxlayernavigation/js/jquery.lazyload"
		}
	},
	"paths": {
		"mlazyload": "MGS_Ajaxlayernavigation/js/jquery.lazyload"
	},
    "shim": {
		"MGS_Ajaxlayernavigation/js/jquery.lazyload": ["jquery"]
	},	
	config: {
		 mixins: {
			 'Smile_ElasticsuiteCatalog/js/range-slider-widget': {
				'MGS_Ajaxlayernavigation/js/range-slider-widget': true
			}
		 }
	}
};