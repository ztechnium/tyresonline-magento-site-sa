define(['jquery'], function($){
   "use strict";
   return function servicepackjs(menuId, modelId, engineId, cateId, customVaribales)
   {
		// console.log(customVaribales);
		var currency = customVaribales.currency;	
		var basic_service = customVaribales.basic_service;	
		var classic_service =  customVaribales.classic_service;
		var expand_a_service = customVaribales.expand_a_service;	
		var expand_b_service = customVaribales.expand_b_service;
		var standardDiscount = customVaribales.standardDiscount;
		var premiumDiscount = customVaribales.premiumDiscount;
		var superbDiscount = customVaribales.superbDiscount;
		var standard_service_pack_name = customVaribales.standard_service_pack_name;	
		var premium_service_pack_name = customVaribales.premium_service_pack_name;	
		var superb_service_pack_name = customVaribales.superb_service_pack_name;	
		var standardservice_productid = customVaribales.standardservice_productid;	
		var premiumservice_productid = customVaribales.premiumservice_productid;	
		var superbservice_productid = customVaribales.superbservice_productid;
		var oilChangeAjaxCallUrl = customVaribales.oilchange_ajax_Url;
		var filterAjaxCallUrl = customVaribales.autofilterproduct_ajax_Url;
		var oileditAjaxCallUrl = customVaribales.oiledit_ajax_Url;
		
		jQuery('.filter-services[type=checkbox]').on('change',function() {
		var total;
		var grandtotal;
		var serviceType = jQuery(this).val();
		jQuery('.service-packs-main').attr('id', '');
		jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
		if(this.checked){
			jQuery('#active-service-packs .oil-change .service-wrap .section-loader').show();
			var assemblyGroupNodeIds = jQuery(this).attr('data-autocat-id');
			var carId = jQuery(this).attr('data-car-id');
			jQuery('.service-packages-block').trigger('processStart');
			jQuery.ajax({
			  type: 'POST',
			  url: filterAjaxCallUrl,
			  data: {cateid: assemblyGroupNodeIds, car_id: carId},
			  cache: true,
			  async: false,
			  success: function (resultData) {
				  //console.log(resultData);
				  if(resultData.price_amount){
					  var price = currency+' '+resultData.price_amount;
						if(serviceType == 'Oil Filter'){
							jQuery('#active-service-packs .oil-filter-apply').addClass('checked');
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-price').val(price);
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-price-amount').val(resultData.price_amount);  
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-id').val(resultData.product_id);  
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-sku').val(resultData.sku);  
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #selected-brand-id').val(resultData.brand_id);  
							jQuery('#active-service-packs .service-packages-block').trigger('processStop');
							jQuery('#active-service-packs .oil-air-filter-div').removeClass('disabled');
							jQuery('#active-service-packs .oil-air-filter-div input').removeAttr('disabled');
							total = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-price-amount').val());
							total = total.toFixed(2);
							jQuery('#active-service-packs .oil-change-grand-total #final-total').val(total);
							//total = currency+' '+total;
							jQuery('#active-service-packs .oil-change-right-section .price_box_home .price').text(total);
							jQuery('#active-service-packs #oil-service-type').text(classic_service);
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service').addClass('active');
							jQuery('#active-service-packs .oil-change-right-section .oil-filter-service.active #add-product-id').val(resultData.product_id);
							jQuery('#active-service-packs .oil-filter-edit').show();
							jQuery('#active-service-packs .oil-change-right-section #package_type').val(classic_service);
							jQuery('#active-service-packs .services-selection .compare-oil-filter-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-car-check-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-carwash-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-car-pickupdrop').addClass('item-inc');
							//serviceComparison(); // trigger compare service
						}
						if(serviceType == 'Air Filter'){
							jQuery('#active-service-packs .oil-air-filter-apply').addClass('checked');
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-price').val(price);
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-price-amount').val(resultData.price_amount);  
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-id').val(resultData.product_id);  
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-sku').val(resultData.sku); 
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #selected-brand-id').val(resultData.brand_id);					
							jQuery('#active-service-packs .service-packages-block').trigger('processStop');
							jQuery('#active-service-packs .oil-ac-filter-div').removeClass('disabled');
							jQuery('#active-service-packs .oil-ac-filter-div input').removeAttr('disabled');
							jQuery('#active-service-packs .oil-filter-div').addClass('disabled');
							jQuery('#active-service-packs .oil-filter-div input').attr('disabled','disabled');
							total = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-price-amount').val());
							total = total.toFixed(2);
							jQuery('#active-service-packs .oil-change-grand-total #final-total').val(total);
							//total = currency+' '+total;
							jQuery('#active-service-packs .oil-change-right-section .price_box_home .price').text(total);
							jQuery('#active-service-packs #oil-service-type').text(expand_a_service);
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service').addClass('active');
							jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service.active #add-product-id').val(resultData.product_id);
							jQuery('#active-service-packs .oil-air-filter-edit').show();
							jQuery('#active-service-packs .oil-change-right-section #package_type').val(expand_a_service);
							jQuery('#active-service-packs .services-selection .compare-air-filter-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-computer-diagnostic-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-fluids-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-windscreen-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .fluid-top-selection-box .all-selected').removeClass('item-inc');
							//serviceComparison(); // trigger compare service
						}
						if(serviceType == 'AC Filter'){
							jQuery('#active-service-packs .oil-ac-filter-apply').addClass('checked');
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-price').val(price);
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-price-amount').val(resultData.price_amount);  
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-id').val(resultData.product_id);  
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-sku').val(resultData.sku);  
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #selected-brand-id').val(resultData.brand_id);	
							jQuery('#active-service-packs .service-packages-block').trigger('processStop');
							total = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-price-amount').val());
							total = total.toFixed(2);
							jQuery('#active-service-packs .oil-change-grand-total #final-total').val(total);
							//total = currency+' '+total;
							jQuery('#active-service-packs .oil-change-right-section .price_box_home .price').text(total);
							jQuery('#active-service-packs #oil-service-type').text(expand_b_service);
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service').addClass('active');
							jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service.active #add-product-id').val(resultData.product_id);
							jQuery('#active-service-packs .oil-air-filter-div').addClass('disabled');
							jQuery('#active-service-packs .oil-air-filter-div input').attr('disabled','disabled');
							jQuery('#active-service-packs .oil-ac-filter-edit').show();
							jQuery('#active-service-packs .oil-change-right-section #package_type').val(expand_b_service);
							jQuery('#active-service-packs .services-selection .compare-ac-filter-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-spark-plug-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-breakpad-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .compare-ac-gas-info').addClass('item-inc');
							jQuery('#active-service-packs .services-selection .fluid-top-selection-box .all-selected').addClass('item-inc');
							//serviceComparison(); // trigger compare service
						}
				  }else{
						jQuery('.oilservicepacks-enquiry').trigger('click');
						return false;
				  }
				
			  }
			});
			jQuery('#active-service-packs .oil-change .service-wrap .section-loader').hide();
		}else{
			grandtotal = jQuery('#active-service-packs .oil-change-grand-total #final-total').val();
			if(serviceType == 'Oil Filter'){
				jQuery('#active-service-packs .oil-filter-apply').removeClass('checked');
				total = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-price-amount').val();
				jQuery('#active-service-packs #oil-service-type').text(basic_service);
				jQuery('#active-service-packs .oil-change-right-section .oil-filter-service').removeClass('active');
				jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #add-product-id').val('');
				jQuery('#active-service-packs .oil-air-filter-div').addClass('disabled');
				jQuery('#active-service-packs .oil-air-filter-div input').attr('disabled','disabled');
				jQuery('#active-service-packs .oil-filter-div input').prop('checked', false);
				jQuery('#active-service-packs .oil-filter-edit').hide();
				jQuery('#active-service-packs .oil-change-right-section #package_type').val(basic_service);
				jQuery('#active-service-packs .services-selection .compare-oil-filter-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-car-check-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-carwash-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-car-pickupdrop').removeClass('item-inc');
				//serviceComparison(); // trigger compare service
			}
			if(serviceType == 'Air Filter'){
				jQuery('#active-service-packs .oil-air-filter-apply').removeClass('checked');
				total = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-price-amount').val();
				jQuery('#active-service-packs #oil-service-type').text(classic_service);
				jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service').removeClass('active');
				jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #add-product-id').val('');
				jQuery('#active-service-packs .oil-filter-div').removeClass('disabled');
				jQuery('#active-service-packs .oil-filter-div input').removeAttr('disabled');
				jQuery('#active-service-packs .oil-ac-filter-div').addClass('disabled');
				jQuery('#active-service-packs .oil-ac-filter-div input').attr('disabled','disabled');
				jQuery('#active-service-packs .oil-air-filter-div input').prop('checked', false);
				jQuery('#active-service-packs .oil-air-filter-edit').hide();
				jQuery('#active-service-packs .oil-change-right-section #package_type').val(classic_service);
				jQuery('#active-service-packs .services-selection .compare-air-filter-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-computer-diagnostic-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-fluids-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-windscreen-info').removeClass('item-inc');
				//serviceComparison(); // trigger compare service
			}
			if(serviceType == 'AC Filter'){
				jQuery('#active-service-packs .oil-ac-filter-apply').removeClass('checked');
				total = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-price-amount').val();
				jQuery('#active-service-packs #oil-service-type').text(expand_a_service);
				jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service').removeClass('active');
				jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #add-product-id').val('');
				jQuery('#active-service-packs .oil-air-filter-div').removeClass('disabled');
				jQuery('#active-service-packs .oil-air-filter-div input').removeAttr('disabled');
				jQuery('#active-service-packs .oil-ac-filter-div input').prop('checked', false);
				jQuery('#active-service-packs .oil-ac-filter-edit').hide();
				jQuery('#active-service-packs .oil-change-right-section #package_type').val(expand_a_service);
				jQuery('#active-service-packs .services-selection .compare-ac-filter-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-spark-plug-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-breakpad-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .compare-ac-gas-info').removeClass('item-inc');
				jQuery('#active-service-packs .services-selection .fluid-top-selection-box .all-selected').removeClass('item-inc');
				//serviceComparison(); // trigger compare service
			}

			total = parseFloat(grandtotal) - parseFloat(total);
			total = total.toFixed(2);
			if(!isNaN(total)){
				jQuery('#active-service-packs .oil-change-grand-total #final-total').val(total);
				jQuery('#active-service-packs .oil-change-right-section .price_box_home .price').text(total);
			}else{
				return false;
			}
		}
		jQuery('.mobile-yourenquiry .inner').html('');
			var $html = jQuery('.oil-filter-box .checkbox-list').clone();
			jQuery('.mobile-yourenquiry .inner').html($html);
			serviceComparison();
		 });
	  
		function serviceComparison(type){
			var oilServiceType = jQuery('#active-service-packs .oil-change-right-section #package_type').val();
			jQuery('#active-service-packs .compare-service-packages-info ul li .icon').addClass('fa');
			jQuery('#active-service-packs .compare-service-packages-info ul li .icon').removeClass('fa-ban');
			jQuery('#active-service-packs .compare-service-packages-info ul li .icon').removeClass('fa-check');
			var total;
			var standardServiceBasePrice = jQuery('#standard-package-base-price').val();
			var premiumServiceBasePrice = jQuery('#premium-package-base-price').val();
			var superbServiceBasePrice = jQuery('#superb-package-base-price').val();
			var standardPrice;
			var premiumPrice;
			var superbPrice;
			
			if(oilServiceType == 'Classic'){
				jQuery('#active-service-packs .compare-service-packages #package_type').val(standard_service_pack_name);
				jQuery('#active-service-packs .compare-service-packages .pack-name').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .pack-price').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .standard-service-package-block').removeClass('hide');
				jQuery('#active-service-packs .compare-service-packages .standard-service-package-price.pack-price').removeClass('hide');
				
				
				var oilProductId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-id').val();
				jQuery('#active-service-packs .service-packagestype-form #add-oil-change-product-id').val(oilProductId);
				
				var oilperLitre = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic.active #add-product-oil-litre').val();
				jQuery('#active-service-packs .service-packagestype-form #add-product-oil-litre').val(oilperLitre);
				
				var oilfilterProductId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-id').val();
				jQuery('#active-service-packs .service-packagestype-form #add-oil-filter-product-id').val(oilfilterProductId);
				jQuery('#active-service-packs .service-packagestype-form #add-air-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-ac-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val(standardservice_productid);
			}
			if(oilServiceType == 'Expanded A'){
			    jQuery('#active-service-packs .compare-service-packages #package_type').val(premium_service_pack_name);
				jQuery('#active-service-packs .compare-service-packages .pack-name').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .pack-price').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .premium-service-package-block').removeClass('hide');
				jQuery('#active-service-packs .compare-service-packages .premium-service-package-price.pack-price').removeClass('hide');	


				var airfilterProductId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-id').val();
				jQuery('#active-service-packs .service-packagestype-form #add-air-filter-product-id').val(airfilterProductId);
				jQuery('#active-service-packs .service-packagestype-form #add-ac-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val(premiumservice_productid);	
				
			}
			if(oilServiceType == 'Expanded B'){ 
				jQuery('#active-service-packs .compare-service-packages #package_type').val(superb_service_pack_name);
				jQuery('#active-service-packs .compare-service-packages .pack-name').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .pack-price').addClass('hide');
				jQuery('#active-service-packs .compare-service-packages .superb-service-package-block').removeClass('hide');
				jQuery('#active-service-packs .compare-service-packages .superb-service-package-price.pack-price').removeClass('hide');
				
				var acfilterProductId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-id').val();
				jQuery('#active-service-packs .service-packagestype-form #add-ac-filter-product-id').val(acfilterProductId);
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val(superbservice_productid);
			}
			if(oilServiceType == 'Basic'){
				jQuery('#active-service-packs .service-packagestype-form #add-oil-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-air-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-ac-filter-product-id').val('');
				jQuery('#active-service-packs .service-packagestype-form #add-current-service-package-id').val('');
			}	
			
			/* Start total calculation */
			standardPrice = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(standardServiceBasePrice);
			standardPrice = standardPrice.toFixed(2);
			var standardServiceDiscounted = (standardPrice * standardDiscount / 100);
			standardPrice = standardPrice - standardServiceDiscounted;
			standardPrice = standardPrice.toFixed(2);
			jQuery('#active-service-packs .compare-service-packages .standard-service-package-price .price').text(standardPrice);
				
			premiumPrice = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(premiumServiceBasePrice);
			premiumPrice = premiumPrice.toFixed(2);
			var premiumServiceDiscounted = (premiumPrice * premiumDiscount / 100);
			premiumPrice = premiumPrice - premiumServiceDiscounted;
			premiumPrice = premiumPrice.toFixed(2);
			jQuery('#active-service-packs .compare-service-packages .premium-service-package-price .price').text(premiumPrice);
				
			superbPrice = parseFloat(jQuery('#active-service-packs .oil-change-grand-total #final-total').val()) + parseFloat(superbServiceBasePrice);
			superbPrice = superbPrice.toFixed(2);
			var superbServiceDiscounted = (superbPrice * superbDiscount / 100);
			superbPrice = superbPrice - superbServiceDiscounted;
			superbPrice = superbPrice.toFixed(2);
			jQuery('#active-service-packs .compare-service-packages .superb-service-package-price .price').text(superbPrice);
				
			/* End total calculation */
		}
	  
		jQuery(document).on('click', ".oil-change-edit", function () {	
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			
			var assemblyGroupNodeIds = jQuery(this).attr('data-autocat-id');
			var editBrandLabel = jQuery(this).attr('data-brand-label');
			var editFilterLabel = jQuery(this).attr('data-filter-label');
			var carId = jQuery(this).attr('data-car-id');
			var noteText = jQuery(this).attr('data-note');
			var oil_litre = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-oil-litre').val();
			var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #selected-brand-id').val();
			var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-id').val();
			jQuery('#active-service-packs .oil-change .service-wrap .section-loader').show();
			var callFrom = 'oilchange';
			var modalBox = jQuery(this).attr('data-modal');
			jQuery("#" + modalBox).fadeOut(jQuery(this).data());
			jQuery("#active-service-packs #" + modalBox).fadeIn(jQuery(this).data());
			
			jQuery.ajax({
			  type: 'POST',
			  url: oileditAjaxCallUrl,
			  data: {cateid: assemblyGroupNodeIds, car_id: carId, oil_litre: oil_litre, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
			  cache: true,
			  success: function (resultData) {
					//console.log(resultData.output);
					jQuery('.selection-box .package_box_edit_response').html('');
					jQuery('#active-service-packs .selection-box .package_box_edit_response').html(resultData.output);
					jQuery('#active-service-packs .oil-change .service-wrap .section-loader').hide();
			  }
			});  
		});
		
		//jQuery('.oil-filter-edit, .oil-air-filter-edit, .oil-ac-filter-edit').on('click',function() {
		jQuery(document).on('click', ".oil-filter-edit, .oil-air-filter-edit, .oil-ac-filter-edit", function () {	
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			
			/* if (jQuery("#active-service-packs .compare-action-block .service-comparision").hasClass("active")) {
				jQuery('.mobile-view .section-4, .mobile-view .section-1').toggle();
			}
			
			jQuery('#active-service-packs .package_box.left-section').hide();  
			jQuery('#active-service-packs .package_box_edit').show(); */  
			
			var assemblyGroupNodeIds = jQuery(this).attr('data-autocat-id');
			var editBrandLabel = jQuery(this).attr('data-brand-label');
			var editFilterLabel = jQuery(this).attr('data-filter-label');
			var carId = jQuery(this).attr('data-car-id');
			var noteText = jQuery(this).attr('data-note');
			if(editFilterLabel == 'Oil Filter'){
				var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #selected-brand-id').val();
				var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-id').val();
			}
			if(editFilterLabel == 'Air Filter'){
				var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #selected-brand-id').val();
				var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-id').val();
			}
			if(editFilterLabel == 'AC Filter'){
				var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #selected-brand-id').val();
				var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-id').val();
			}
			var callFrom = 'oilchange';
			jQuery('#active-service-packs .oil-change .service-wrap .section-loader').show();
			
			var modalBox = jQuery(this).attr('data-modal');
			jQuery("#" + modalBox).fadeOut(jQuery(this).data());
			jQuery("#active-service-packs #" + modalBox).fadeIn(jQuery(this).data());
			
			jQuery.ajax({
				  type: 'POST',
				  url: oileditAjaxCallUrl,
				  data: {cateid: assemblyGroupNodeIds, car_id: carId, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
				  cache: true,
				  success: function (resultData) {
						jQuery('.selection-box .package_box_edit_response').html('');
						jQuery('#active-service-packs .selection-box .package_box_edit_response').html(resultData.output);
						jQuery('#active-service-packs .oil-change .service-wrap .section-loader').hide();
				  }
				});  
		});
		
		jQuery('.change-compare-service').on('click',function() {
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			
			var serviceType = jQuery(this).attr('data-service');
			var assemblyGroupNodeIds = jQuery(this).attr('data-autocat-id');
			var editBrandLabel = jQuery(this).attr('data-brand-label');
			var editFilterLabel = jQuery(this).attr('data-filter-label');
			var carId = jQuery(this).attr('data-car-id');
			var noteText = jQuery(this).attr('data-note');
			var callFrom = 'servicepack';
			if(serviceType == 'oil-change'){
				/* if (jQuery(window).width() <= 767){
					jQuery('#active-service-packs .oil-filter-box .oil-change-edit').trigger('click');
				}else{
					var oil_litre = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-oil-litre').val();
					var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #selected-brand-id').val();
					var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-id').val();
					//jQuery('#active-service-packs .compare-service-packages-info').hide();
					//jQuery('#active-service-packs .edit-oilchange-packages').show();
					jQuery('#active-service-packs .service-wrap .services-selection .section-loader').show();
					jQuery.ajax({
						  type: 'POST',
						  url: oileditAjaxCallUrl,
						  data: {cateid: assemblyGroupNodeIds, car_id: carId, oil_litre: oil_litre, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
						  cache: true,
						  success: function (resultData) {
								//console.log(resultData.output);
								jQuery('#active-service-packs .selection-box .service_package_box_edit_response').html(resultData.output);
								jQuery('#active-service-packs .selection-box .service_package_box_edit_response .box').addClass('bg-red-600');
								jQuery('#active-service-packs .service-wrap .services-selection .section-loader').hide();
						  }
					});
				} */
				var oil_litre = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-oil-litre').val();
				var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #selected-brand-id').val();
				var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-change-basic #product-id').val();
				//jQuery('#active-service-packs .compare-service-packages-info').hide();
				//jQuery('#active-service-packs .edit-oilchange-packages').show();
				jQuery('#active-service-packs .service-wrap .services-selection .section-loader').show();
				jQuery.ajax({
					  type: 'POST',
					  url: oileditAjaxCallUrl,
					  data: {cateid: assemblyGroupNodeIds, car_id: carId, oil_litre: oil_litre, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
					  cache: true,
					  success: function (resultData) {
							//console.log(resultData.output);
							jQuery('.selection-box .service_package_box_edit_response').html('');
							jQuery('#active-service-packs .selection-box .service_package_box_edit_response').html(resultData.output);
							jQuery('#active-service-packs .selection-box .service_package_box_edit_response .box').addClass('bg-red-600');
							jQuery('#active-service-packs .service-wrap .services-selection .section-loader').hide();
					  }
				});
			}
			/* if (jQuery(window).width() <= 767){
				if(serviceType == 'oil-filter'){
					jQuery('#active-service-packs .oil-filter-box .oil-filter-edit').trigger('click');
				}
				if(serviceType == 'oil-air-filter'){
					jQuery('#active-service-packs .oil-filter-box .oil-air-filter-edit').trigger('click');
				}
				if(serviceType == 'oil-ac-filter'){
					jQuery('#active-service-packs .oil-filter-box .oil-ac-filter-edit').trigger('click');
				}
			}else{
				if(serviceType == 'oil-filter' || serviceType == 'oil-air-filter' || serviceType == 'oil-ac-filter'){
					if(editFilterLabel == 'Oil Filter'){
						var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #selected-brand-id').val();
						var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-id').val();
					}
					if(editFilterLabel == 'Air Filter'){
						var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #selected-brand-id').val();
						var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-id').val();
					}
					if(editFilterLabel == 'AC Filter'){
						var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #selected-brand-id').val();
						var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-id').val();
					}
					
					//jQuery('#active-service-packs .compare-service-packages-info').hide();
					//jQuery('#active-service-packs .edit-oilchange-packages').show();
					jQuery('#active-service-packs .service-wrap .services-selection .section-loader').show();
					
					jQuery.ajax({
						  type: 'POST',
						  url: oileditAjaxCallUrl,
						  data: {cateid: assemblyGroupNodeIds, car_id: carId, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
						  cache: true,
						  success: function (resultData) {
								//console.log(resultData.output);
								jQuery('#active-service-packs .selection-box .service_package_box_edit_response').html(resultData.output);
								jQuery('#active-service-packs .selection-box .service_package_box_edit_response .box').addClass('bg-red-600');
								jQuery('#active-service-packs .service-wrap .services-selection .section-loader').hide();
						  }
					});
					
				}
			} */
			
			if(serviceType == 'oil-filter' || serviceType == 'oil-air-filter' || serviceType == 'oil-ac-filter'){
				if(editFilterLabel == 'Oil Filter'){
					var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #selected-brand-id').val();
					var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-filter-service #product-id').val();
				}
				if(editFilterLabel == 'Air Filter'){
					var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #selected-brand-id').val();
					var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-air-filter-service #product-id').val();
				}
				if(editFilterLabel == 'AC Filter'){
					var selectedBrandId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #selected-brand-id').val();
					var selectedProductId = jQuery('#active-service-packs .oil-change-right-section .oil-ac-filter-service #product-id').val();
				}
				
				//jQuery('#active-service-packs .compare-service-packages-info').hide();
				//jQuery('#active-service-packs .edit-oilchange-packages').show();
				jQuery('#active-service-packs .service-wrap .services-selection .section-loader').show();
				
				jQuery.ajax({
					  type: 'POST',
					  url: oileditAjaxCallUrl,
					  data: {cateid: assemblyGroupNodeIds, car_id: carId, edit_brand_label: editBrandLabel, edit_filter_label: editFilterLabel, selected_brand_id: selectedBrandId, selected_product_id: selectedProductId, note_text: noteText, call_from: callFrom},
					  cache: true,
					  success: function (resultData) {
							//console.log(resultData.output);
							jQuery('.selection-box .service_package_box_edit_response').html('');
							jQuery('#active-service-packs .selection-box .service_package_box_edit_response').html(resultData.output);
							jQuery('#active-service-packs .selection-box .service_package_box_edit_response .box').addClass('bg-red-600');
							jQuery('#active-service-packs .service-wrap .services-selection .section-loader').hide();
					  }
				});
				
			}
			
		});
		
		/* jQuery('.service-edit-div-radio-input[type=radio]').on('change',function() {
			var packageId = jQuery(this).attr('id');
			if(packageId == 'standard'){
				if(jQuery("#active-service-packs .oil-ac-filter-div .filter-services[type=checkbox]").prop('checked') == true){
					jQuery('#active-service-packs .oil-filter-box .oil-ac-filter-div .filter-services[type=checkbox]').prop("checked", false).trigger("change"); // trigger ac filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				if(jQuery("#active-service-packs .oil-air-filter-div .filter-services[type=checkbox]").prop('checked') == true){
					jQuery('#active-service-packs .oil-filter-box .oil-air-filter-div .filter-services[type=checkbox]').prop("checked", false).trigger("change"); // trigger air filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				
			}
			if(packageId == 'premium'){
				if(jQuery("#active-service-packs .oil-filter-div .filter-services[type=checkbox]").prop('checked') == false){
					jQuery('#active-service-packs .enquiry-selection.oil-filter-box .oil-filter-div .filter-services[type=checkbox]').prop("checked", true).trigger("change"); // trigger oil filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				if(jQuery("#active-service-packs .oil-air-filter-div .filter-services[type=checkbox]").prop('checked') == false){
					jQuery('#active-service-packs .oil-filter-box .oil-air-filter-div .filter-services[type=checkbox]').prop("checked", true).trigger("change"); // trigger air filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				if(jQuery("#active-service-packs .oil-ac-filter-div .filter-services[type=checkbox]").prop('checked') == true){
					jQuery('#active-service-packs .oil-filter-box .oil-ac-filter-div .filter-services[type=checkbox]').prop("checked", false).trigger("change"); // trigger ac filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				
			}
			if(packageId == 'superb'){
				if(jQuery("#active-service-packs .oil-air-filter-div .filter-services[type=checkbox]").prop('checked') == false){
					jQuery('#active-service-packs .oil-filter-box .oil-air-filter-div .filter-services[type=checkbox]').prop("checked", true).trigger("change"); // trigger air filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
				if(jQuery("#active-service-packs .oil-ac-filter-div .filter-services[type=checkbox]").prop('checked') == false){
					jQuery('#active-service-packs .oil-filter-box .oil-ac-filter-div .filter-services[type=checkbox]').prop("checked", true).trigger("change"); // trigger ac filter
					jQuery('#active-service-packs #active-service-package').val(packageId);
				}
			}
		}); */
		
		jQuery('.service-packages-edit').on('click',function(e) {
			e.preventDefault();
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			//var modalBox = jQuery(this).attr('data-modal');
			//jQuery("#" + modalBox).fadeOut(jQuery(this).data());
			//jQuery("#active-service-packs #" + modalBox).fadeIn(jQuery(this).data());
			
			var packageSelected = jQuery(this).attr('data-package');
			jQuery('#active-service-packs .servicePack-edit-list .service-edit-div-radio input').prop("checked", false);
			jQuery('#active-service-packs .servicePack-edit-list .service-edit-div-radio #'+packageSelected).prop("checked", true);
		});
		
		jQuery('.service-comparision-email').on('click',function() {
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			var oildata_array = jQuery("#active-service-packs .service-packages-form").serialize();
			var servicepacks_array = jQuery("#active-service-packs .service-packagestype-form").serialize();
			jQuery('#oilserialize-data').val(oildata_array);
			jQuery('#servicepacksserialize-data').val(servicepacks_array);
			jQuery('#serviceform-type').val('submit');
		});
		jQuery('.service-comparision-download').on('click',function() {
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			var oildata_array = jQuery("#active-service-packs .service-packages-form").serialize();
			var servicepacks_array = jQuery("#active-service-packs .service-packagestype-form").serialize();
			jQuery('#oilserialize-data').val(oildata_array);
			jQuery('#servicepacksserialize-data').val(servicepacks_array);
			jQuery('#serviceform-type').val('download');
		});
		
		jQuery("a[data-modal]").click(function(e) {
			jQuery('.service-packs-main').attr('id', '');
			jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			var modalBox = jQuery(this).attr('data-modal');
			jQuery("#" + modalBox).hide();
			jQuery("#active-service-packs #" + modalBox).fadeIn(jQuery(this).data());
		});
		
		jQuery('.service-edit-div-radio-input[type=radio]').on('change',function() {
			//jQuery('.service-packs-main').attr('id', '');
			//jQuery(this).closest('.service-packs-main').attr('id', 'active-service-packs');
			jQuery('#active-service-packs .selection-box .box-close').trigger('click');
			//var packageId = jQuery(this).attr('id');
			var packageType = jQuery(this).attr('data-service-type');
			
			if(packageType == 'standard'){
				 if (jQuery(window).width() > 767 ){
					 jQuery('html, body').animate({
					  scrollTop: jQuery("div.standard-service-step").offset().top - 150
					}, 1000);
				 }
				jQuery('.service-packs-main').attr('id', '');
				jQuery('.standard-service-step').attr('id', 'active-service-packs');
				jQuery('.service-box-slider').trigger('to.owl.carousel', 2);
			}
			
			if(packageType == 'premium'){
				if (jQuery(window).width() > 767 ){
					jQuery('html, body').animate({
					  scrollTop: jQuery("div.premium-service-step").offset().top - 150
					}, 1000);	
				}
				jQuery('.service-packs-main').attr('id', '');
				jQuery('.premium-service-step').attr('id', 'active-service-packs');
				jQuery('.service-box-slider').trigger('to.owl.carousel', 1);
				
			}
			if(packageType == 'superb'){
				if (jQuery(window).width() > 767 ){
					jQuery('html, body').animate({
					  scrollTop: jQuery("div.superb-service-step").offset().top - 150
					}, 1000);
				}
				jQuery('.service-packs-main').attr('id', '');
				jQuery('.superb-service-step').attr('id', 'active-service-packs');
				jQuery('.service-box-slider').trigger('to.owl.carousel', 0);
			}
		});	
	  
   }
   
});