require([
    'jquery',
    'bootstrap'
    ], function ($, bootstrap) {
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
});
});

require(["jquery"], function($) {
 $(window).on("load", function () {
   $('.preloader').fadeOut('50');
   $('body').removeClass('overflow-hidden');
});
});

require(["jquery"], function($) {
$(window).scroll(function(){
  if ($(window).scrollTop() >= 150) {
    $('header').addClass('fixed');
   }
   else {
    $('header').removeClass('fixed');
   }
});
});

require(["jquery"], function($) {
jQuery(window).on('scroll', function() {
    if (jQuery(this).scrollTop() > 100) {
      jQuery('.addtocart-stickybar').addClass('active');
    } else {
      jQuery('.addtocart-stickybar').removeClass('active');
    }
  });  
});

require(["jquery", "mage/cookies"], function($) {
    function syncFormKeysFromCookie() {
        var key = $.mage.cookies.get('form_key');
        if (!key) {
            return;
        }
        $('input[name="form_key"]').val(key);
    }
    // Run ASAP (not only on DOMReady) to avoid stale cached form_key
    syncFormKeysFromCookie();
    $(document).ready(syncFormKeysFromCookie);
    // Ensure the correct key is applied right before any submit
    $(document).on('submit', 'form', syncFormKeysFromCookie);
    $(document).on('click', 'button[type=\"submit\"], input[type=\"submit\"]', syncFormKeysFromCookie);
    $(document).on('ajaxSend ajaxComplete', syncFormKeysFromCookie);
});

require(["jquery", "select2"], function($) {
$(document).ready(function() {
    $('.select2').select2({
		dropdownCssClass: "dropdown-style1"
    });
	$('.select2-social-login-popup').select2({
		dropdownCssClass: "dropdown-style1",
		dropdownParent: $("#social-login-popup")
    });
	$('.select2-nosearch').select2({
		dropdownCssClass: "dropdown-style2",
		minimumResultsForSearch: -1
	});
	$('.select2-qty').select2({
		dropdownCssClass: "dropdown-style2 text-center",
		minimumResultsForSearch: -1
	});
	$('.select2-qty-search').select2({
		dropdownCssClass: "dropdown-style3 text-center",
	});
	$('.select2-tyre-size-search').select2({
		dropdownCssClass: "dropdown-style3 text-center",
		placeholder: "أدخل مقاس الإطار",
		language: {
			inputTooShort: function(args) {
				var remainingChars = args.minimum - args.input.length;
				var message = 'الرجاء إدخال ' + remainingChars + ' أحرف أو أكثر';
				return message;
			}
		},
		minimumInputLength: 3,
		maximumSelectionLength: 2,
		matcher: matchCustom
	});

	function matchCustom(params, data) {
		// If there are no search terms, return all of the data
		if ($.trim(params.term) === '') {
		  return data;
		}
	  
		// Do not display the item if there is no 'text' property
		if (typeof data.text === 'undefined') {
		  return null;
		}
	  
		// `params.term` should be the term that is used for searching
		// `data.text` is the text that is displayed for the data object
		if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
		  return data;
		}
	  
		// custom search using lookup data
		if ( typeof $(data.element).data('lookup') !== 'undefined' && $(data.element).data('lookup').toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
			return data;
		}	
	  
		// Return `null` if the term should not be displayed
		return null;
	  }
});
$(document).ajaxStop(function () {
    /*$('.checkout-index-index .vehicle-dropdown select').addClass('select2');
    $('.select2').select2();*/
    $('.checkout-index-index .vehicle-dropdown .select').select2({
      dropdownCssClass: "dropdown-style1"
    });
    $('#shipping-new-address-form .control .select').select2({
      dropdownCssClass: "dropdown-style1"
    });
  });
});


require(["jquery", "lazysizes"], function($) {
document.addEventListener('lazybeforeunveil', function(e){
    var bg = e.target.getAttribute('data-bg');
    if(bg){
        e.target.style.backgroundImage = 'url(' + bg + ')';
    }
});
});


require(["jquery", "scrollbar"], function($) {
    jQuery('.scrollbar-inner').scrollbar();
    jQuery('.scrollbar-outer').scrollbar();
});

require(['jquery'],function($){
  $(document).ajaxStop(function () {
    //$('#companyDtl').change(function() {
    $(document).on('change', "#companyDtl", function() {
        var ischecked= $(this).is(':checked');
        if (ischecked) {
          $(".company_field_div").show();
          $(".vat_field_div").show();
        } else {
          $(".company_field_div").hide();
          $(".vat_field_div").hide();
        }
    });
  });
});



require(['jquery'],function($){
    $(document).on('click', ".fsloaderclose", function() {
        $('.loading-mask').css('display','none');
    });
	$(document).on('click', ".selected-size, .tyre-search .search-wrap input", function() {
		$(".frontWidthLabel").trigger( "click" );
	});
	$(document).on('click', ".tyre-search .search-vehicle span", function() {
		$(".vehicle_make").trigger( "click" );
	});
});


require(["jquery", "izimodal"], function($) {
$(document).ready(function() {  
  if($('.izimodal').length) {
    $('.izimodal').iziModal({
      overlayColor: 'rgba(255, 255, 255, 0.9)',
      bodyOverflow: true,
      width: false,
      background: '#D70000',
      radius: false,
      transitionIn: false,
      transitionOut: false,
      transitionInOverlay: false,
      transitionOutOverlay: false
    });
  }
  
  $(document).on('click', '.info-video', function (event) {
    event.preventDefault();
    var videoUrl = $(this).data('href');
    var videoTitle = $(this).data('title');
    if($('#info-video').length) {
      $("#info-video").iziModal({
        history: false,
        iframe: true,
        iframeURL: videoUrl,
        title:videoTitle,
        iframeHeight: 500,
        width: 1000,
        fullscreen: true,
        headerColor: '#000000',
        transition: 'fadeInDown'
      });
    }
    });
    
    $(document).on('closed', '#info-video', function (e) {
    $('#info-video').iziModal('destroy');
    $('html').removeClass('iziModal-isOverflow');
    $("body").removeAttr("style");
    });
  
	});
});
require(["jquery", "mage/url"], function($, url) {
	$(".header-right .search-toggle").click(function() {
		var getTyreSizeUrl = url.build('tyrefinder/ajax/gettyresize');
		$.ajax({
			url: getTyreSizeUrl,
			type: 'POST',
			data: {
				format: 'json',
			},
			error: function () {
				alert("Error");
			},
			success: function (data) {
			$('.tyre-size-search-selection .select2-tyre-size-search').html(data.response);
			$('.header-search-wrap .select2-tyre-size-search').html(data.response);
			}
		});
	});
	$('.header-search-wrap .select2-tyre-size-search').on('select2:select', function (e) {
		var data = e.params.data;
		var width = $(data.element).data('width');
		var height = $(data.element).data('height');
		var rim = $(data.element).data('rim');
		
		var length = $(this).select2('data').length;
		$('#product-list-limit').remove();
		if(length == 1){
			$('.header-search-wrap .tyre-size-search-form #tyresize-width').val(width);
			$('.header-search-wrap .tyre-size-search-form #tyresize-height').val(height);
			$('.header-search-wrap .tyre-size-search-form #tyresize-rim').val(rim);
		}
		if(length == 2){
			$('.header-search-wrap .tyre-size-search-form #tyresize-rear-width').val(width);
			$('.header-search-wrap .tyre-size-search-form #tyresize-rear-height').val(height);
			$('.header-search-wrap .tyre-size-search-form #tyresize-rear-rim').val(rim);
			$('<input>').attr({type: 'hidden',name: 'product_list_limit',value: 500, id:'product-list-limit'}).appendTo('.header-search-wrap .tyre-size-search-form');
		}
	});
	$('.header-search-wrap .select2-tyre-size-search').on('select2:unselect', function (e) {
		$('.header-search-wrap .tyre-size-search-form #tyresize-width').val('');
		$('.header-search-wrap .tyre-size-search-form #tyresize-height').val('');
		$('.header-search-wrap .tyre-size-search-form #tyresize-rim').val('');
		$('.header-search-wrap .tyre-size-search-form #tyresize-rear-width').val('');
		$('.header-search-wrap .tyre-size-search-form #tyresize-rear-height').val('');
		$('.header-search-wrap .tyre-size-search-form #tyresize-rear-rim').val('');
		$('#product-list-limit').remove();
		var selectedWidth = $('.header-search-wrap .select2-tyre-size-search').find(':selected').data('width');
		var selectedHeight = $('.header-search-wrap .select2-tyre-size-search').find(':selected').data('height');
		var selectedRim = $('.header-search-wrap .select2-tyre-size-search').find(':selected').data('rim');
		if(selectedWidth && selectedHeight && selectedRim){
			$('.header-search-wrap .tyre-size-search-form #tyresize-width').val(selectedWidth);
			$('.header-search-wrap .tyre-size-search-form #tyresize-height').val(selectedHeight);
			$('.header-search-wrap .tyre-size-search-form #tyresize-rim').val(selectedRim);
		}

	});
	/* inside width popup tyre size start */
	$('.tyre-size-search-selection .select2-tyre-size-search').on('select2:select', function (e) {
		var data = e.params.data;
		var width = $(data.element).data('width');
		var height = $(data.element).data('height');
		var rim = $(data.element).data('rim');
		
		var length = $(this).select2('data').length;
		$('#product-list-limit').remove();
		if(length == 1){
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-width').val(width);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-height').val(height);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rim').val(rim);
		}
		if(length == 2){
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-width').val(width);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-height').val(height);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-rim').val(rim);
			$('<input>').attr({type: 'hidden',name: 'product_list_limit',value: 500, id:'product-list-limit'}).appendTo('.tyre-size-search-selection .tyre-size-search-form');
		}
	});
	$('.tyre-size-search-selection .select2-tyre-size-search').on('select2:unselect', function (e) {
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-width').val('');
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-height').val('');
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rim').val('');
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-width').val('');
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-height').val('');
		$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rear-rim').val('');
		$('#product-list-limit').remove();
		var selectedWidth = $('.tyre-size-search-selection .select2-tyre-size-search').find(':selected').data('width');
		var selectedHeight = $('.tyre-size-search-selection .select2-tyre-size-search').find(':selected').data('height');
		var selectedRim = $('.tyre-size-search-selection .select2-tyre-size-search').find(':selected').data('rim');
		if(selectedWidth && selectedHeight && selectedRim){
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-width').val(selectedWidth);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-height').val(selectedHeight);
			$('.tyre-size-search-selection .tyre-size-search-form #tyresize-rim').val(selectedRim);
		}

	});
	/* inside width popup tyre size  end */
});

require([
    'jquery',
    'domReady!'
], function ($) {
	$( document ).ready(function(){
		var baseUrl = window.checkout.baseUrl;
		var getTyreSizeUrl = baseUrl+'tyrefinder/ajax/gettyresize';
		$.ajax({
			url: getTyreSizeUrl,
			type: 'POST',
			data: {
				format: 'json',
			},
			error: function () {
				alert("Error");
			},
			success: function (data) {
			$('.tyre-size-search-selection .select2-tyre-size-search').html(data.response);
			$('.header-search-wrap .select2-tyre-size-search').html(data.response);
			}
		});
	});
});