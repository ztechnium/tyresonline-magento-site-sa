define([
	'jquery',
	'ecomteck_storelocator',
	'Ecomteck_StoreLocator/js/libs/handlebars.min'
],
function($,config,Handlebars) {
	window.Handlebars = Handlebars;
	return function (config) {
		$(document).ready(function() {

		//	$.getScript("https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key="+config.apiKey+"&libraries=geometry,places", function () {
				require(['Ecomteck_StoreLocator/js/plugins/storeLocator/jquery.storelocator'],function(){
					initialize();
				});
		//	});

			function initialize() {
				$('#bh-sl-map-container').storeLocator(config);
			}
		});
	};
}
);
