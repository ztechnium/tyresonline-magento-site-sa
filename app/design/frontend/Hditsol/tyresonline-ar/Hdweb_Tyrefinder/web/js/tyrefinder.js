require(["jquery", "Magento_Ui/js/modal/modal"], function ($, modal) {
  /* header.phtml */
  var WindowsSize = function () {
    var h = $(window).height();
  };

  $(document).ready(WindowsSize);
  $(window).resize(WindowsSize);

  jQuery(window).scroll(function () {
    var sticky = jQuery(".searchSec"),
      scroll = jQuery(window).scrollTop();
    if (scroll >= 300) sticky.addClass("sticky");
    else sticky.removeClass("sticky");
  });

  $tyreImgNormal = getViewFileUrl + "/icon/tyre-size-number.png";
  $tyreImg205 = getViewFileUrl + "/icon/tyre-size-number-205.png";
  $tyreImg55 = getViewFileUrl + "/icon/tyre-size-number-55.png";
  $tyreImg16 = getViewFileUrl + "/icon/tyre-size-number-16.png";

  $("#input-search").on("keyup", function () {
    var value = $(this).val().toLowerCase();
    $("#searchUL .li-search").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  $("#tyresize-input-search").on("keyup", function () {
    var value = $(this).val().toLowerCase();
    $(".alltyresize #searchUL .li-search").filter(function () {
      var text = (
        $(this).text().toLowerCase() + $(this).data("lookup")
      ).toLocaleLowerCase();
      $(this).toggle(text.indexOf(value) != -1);
    });
  });

  jQuery(".catalog-product-view .select_qty").on(
    "select2:select",
    function (e) {
      var selectedQty = jQuery(this).val();
      jQuery(".select_qty_btm").val(selectedQty).trigger("change");
    }
  );
  jQuery(".select_qty_btm").on("select2:select", function (e) {
    var selectedQty = jQuery(this).val();
    jQuery(".regular-product-list .select_qty")
      .val(selectedQty)
      .trigger("change");
  });

  jQuery(document).on(
    "change",
    ".regular-product-list .select_qty",
    function () {
      var partsCat = jQuery(this).data("partscat");
      var selectedQty = jQuery(this).val();
      if (selectedQty == 1) {
        selectedQty = 4;
      }
      // var selectedPrice=jQuery(this).parent().parent().find('.single-tyre-price').text().replace(/,/g, '');
      var selectedPrice = jQuery(this)
        .closest(".tyres-productset4")
        .find(".single-tyre-price")
        .text()
        .replace(/,/g, "");
      var selectedPrice = selectedPrice.replace("AED", "");
      selectedPrice = parseFloat(selectedPrice);
      var simpleTotal = parseInt(selectedQty) * selectedPrice;
      if (partsCat == "Tyres" || partsCat == "Lubricants") {
        var discountqty = jQuery(this).data("discountqty");
        var totalqty = jQuery(this).data("totalqty");
        //var selected_qty=jQuery(this).val();
        var selected_productid = jQuery(this).data("id");
        var ruleDiscount = jQuery(this).data("rule-discount");
        if (discountqty != undefined && discountqty != undefined) {
          var unitprice = jQuery(this).data("unitprice");
          var module_qty = selectedQty % totalqty;
          var discounted_qty = selectedQty - module_qty;
          var pricefor_discount_item =
            (discounted_qty * unitprice * discountqty) / 100; //get percenatge
          var offer_price_with_deducted_ammount =
            discounted_qty * unitprice - pricefor_discount_item;
          var pricefor_without_discount_item = unitprice * module_qty;
          var front_chargebl_price =
            offer_price_with_deducted_ammount + pricefor_without_discount_item;
          simpleTotal = front_chargebl_price;
          var actual_price = unitprice * selectedQty;
          actual_price = parseFloat(actual_price).toFixed(2);
          // frontTotal=parseFloat(frontTotal).toFixed(2);
          var setof_fourprice = unitprice * 4;
          setof_fourprice = parseFloat(setof_fourprice).toFixed(2);
          simpleTotal = parseFloat(simpleTotal).toFixed(2);

          var formatedActualTotal = formatPriceWithComma(actual_price);
          var formatedSetFourPrice = formatPriceWithComma(setof_fourprice);

          if (selectedQty >= 4 && offer_price_with_deducted_ammount > 0) {
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .addClass("strike-price");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".setfourprice")
              .html(formatedActualTotal);
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4")
              .html("طقم من " + selectedQty + ":");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .show();
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set2price .set2")
              .hide();
          } else {
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .removeClass("strike-price");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".setfourprice")
              .html(formatedSetFourPrice);
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4")
              .html("طقم من 4:");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .hide();
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set2price .set2")
              .show();
          }
        }

        if (ruleDiscount != undefined) {
          var unitprice = jQuery(this).data("unitprice");
          var setof_fourprice = unitprice * 4;
          setof_fourprice = parseFloat(setof_fourprice).toFixed(2);
          var actual_price = unitprice * selectedQty;
          actual_price = parseFloat(actual_price).toFixed(2);

          var formatedActualTotal = formatPriceWithComma(actual_price);
          var formatedSetFourPrice = formatPriceWithComma(setof_fourprice);

          if (selectedQty >= 4) {
            var ruleDiscountPrice = actual_price - ruleDiscount;
            simpleTotal = parseFloat(ruleDiscountPrice).toFixed(2);
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .addClass("strike-price");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".setfourprice")
              .html(formatedActualTotal);
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4")
              .html("طقم من " + selectedQty + ":");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .show();
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set2price .set2")
              .hide();
          } else {
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .removeClass("strike-price");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".setfourprice")
              .html(formatedSetFourPrice);
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4")
              .html("طقم من 4:");
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .hide();
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set2price .set2")
              .show();
          }
        }

        simpleTotal = parseFloat(simpleTotal).toFixed(2);
        var formatedTotal = formatPriceWithComma(simpleTotal);

        jQuery(this)
          .closest(".tyres-productset4")
          .find(".settwoprice")
          .html(formatedTotal);
        jQuery(this)
          .closest(".tyres-productset4")
          .find(".set2")
          .html("طقم من " + selectedQty + ":");

        jQuery(this).closest(".tyres-productset4").find(".set2price").show();
        jQuery(this).closest(".tyres-productset4").find(".set1price").show();
        //jQuery(this).closest('.tyres-productset4').find('.set4price').show();

        if (discountqty == undefined && discountqty == undefined) {
          if (selectedQty == 4) {
            jQuery(this)
              .closest(".tyres-productset4")
              .find(".set4price")
              .hide();
            if (ruleDiscount != undefined) {
              jQuery(this)
                .closest(".tyres-productset4")
                .find(".set4price")
                .show();
            }
          } else {
            // jQuery(this).closest('.tyres-productset4').find('.set4price').show();
            if (selectedQty == 1) {
              jQuery(this)
                .closest(".tyres-productset4")
                .find(".set1price")
                .hide();
              if (ruleDiscount != undefined) {
                jQuery(this)
                  .closest(".tyres-productset4")
                  .find(".set1price")
                  .show();
              }
            }
          }
        }
      }

      var istabbyAvailable = jQuery(this).data("istabby");
      if (
        parseFloat(simpleTotal) >= tabbyfourday &&
        parseFloat(simpleTotal) <= tabbyinstallmentmaxvalue &&
        istabbyAvailable == "Yes"
      ) {
        jQuery(this)
          .closest(".tyres-productset4")
          .find(".tabby-pay-later-list")
          .removeClass("hidetabbyinstaller");
        jQuery(".tabby-product-detail").show();
      } else {
        jQuery(this)
          .closest(".tyres-productset4")
          .find(".tabby-pay-later-list")
          .addClass("hidetabbyinstaller");
      }
    }
  );

  jQuery(document).on(
    "change",
    ".bundle-product-list .select_qty",
    function () {
      var front_qty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .val();
      var front_price = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".leftbundle")
        .find(".single-tyre-price")
        .text()
        .replace(/,/g, "");

      if (front_qty == 1) {
        jQuery(this)
          .parents(".bundleBox")
          .find(".leftbundle")
          .find(".set2price")
          .css("visibility", "hidden");
      } else {
        jQuery(this)
          .parents(".bundleBox")
          .find(".leftbundle")
          .find(".set2price")
          .css("visibility", "visible");
      }
      front_price = front_price.replace("AED", "");
      front_price = parseFloat(front_price);
      var frontTotal = front_qty * front_price;
      frontTotal = parseFloat(frontTotal).toFixed(2);

      var discountqty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .data("discountqty"); //  Discount Amount percen
      var totalqty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .data("totalqty"); // means  Discount Qty Step (Buy X)
      var selected_qty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .val();
      var ruleDiscountFront = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .data("rule-discount");
      //if(jQuery(this).hasClass('front-qty')){

      var formatedFrontTotal = formatPriceWithComma(frontTotal);
      jQuery(this)
        .parents(".bundleBox")
        .find(".leftbundle")
        .find(".setfourprice")
        .html(formatedFrontTotal);
      jQuery(this)
        .parents(".bundleBox")
        .find(".leftbundle")
        .find(".setOf")
        .html("طقم من " + front_qty + ":");

      var unitprice = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".front-qty")
        .data("unitprice");
      if (discountqty != undefined && discountqty != undefined) {
        var module_qty = selected_qty % totalqty;
        var discounted_qty = selected_qty - module_qty;
        var pricefor_discount_item =
          (discounted_qty * unitprice * discountqty) / 100; //get percenatge
        var offer_price_with_deducted_ammount =
          discounted_qty * unitprice - pricefor_discount_item;
        var pricefor_without_discount_item = unitprice * module_qty;
        var front_chargebl_price =
          offer_price_with_deducted_ammount + pricefor_without_discount_item;
        frontTotal = front_chargebl_price;
        frontTotal = parseFloat(frontTotal).toFixed(2);
        var actual_price = unitprice * selected_qty;
        actual_price = parseFloat(actual_price).toFixed(2);

        var formatedActualPrice = formatPriceWithComma(actual_price);
        var formatedFrontTotal = formatPriceWithComma(frontTotal);

        if (selected_qty >= 4 && offer_price_with_deducted_ammount > 0) {
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".setfourprice")
            .html(formatedActualPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .addClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .show();
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set2price .set2")
            .hide();
        } else {
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .removeClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".setfourprice")
            .html(formatedFrontTotal);
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .hide();
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set2price .set2")
            .show();
        }
        jQuery(this)
          .parents(".bundleBox")
          .find(".leftbundle")
          .find(".set2price .setfourprice")
          .html(formatedFrontTotal);
      }
      if (ruleDiscountFront != undefined) {
        var setof_price = unitprice * selected_qty;
        setof_price = parseFloat(setof_price).toFixed(2);
        var actual_price = unitprice * selected_qty;
        actual_price = parseFloat(actual_price).toFixed(2);

        var formatedActualPrice = formatPriceWithComma(actual_price);
        var formatedSetofPrice = formatPriceWithComma(setof_price);
        var formatedFrontTotal = formatPriceWithComma(frontTotal);

        if (selected_qty >= 4) {
          var ruleDiscountPrice = actual_price - ruleDiscountFront;
          frontTotal = parseFloat(ruleDiscountPrice).toFixed(2);
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .addClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".setfourprice")
            .html(formatedActualPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .show();
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set2price .set2")
            .hide();
        } else {
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .removeClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".setfourprice")
            .html(formatedSetofPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .hide();
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set2price .set2")
            .show();
        }
        jQuery(this)
          .parents(".bundleBox")
          .find(".leftbundle")
          .find(".set2price .setfourprice")
          .html(formatedFrontTotal);
      }

      if (discountqty == undefined && discountqty == undefined) {
        if (selected_qty == 4) {
          jQuery(this)
            .parents(".bundleBox")
            .find(".leftbundle")
            .find(".set4price")
            .hide();
          if (ruleDiscountFront != undefined) {
            jQuery(this)
              .parents(".bundleBox")
              .find(".leftbundle")
              .find(".set4price")
              .show();
          }
        } else {
          if (selected_qty == 1) {
            jQuery(this)
              .parents(".bundleBox")
              .find(".leftbundle")
              .find(".set1price")
              .hide();
            if (ruleDiscountFront != undefined) {
              jQuery(this)
                .parents(".bundleBox")
                .find(".leftbundle")
                .find(".set1price")
                .show();
            }
          }
        }
      }
      //}

      var rear_qty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .val();
      var rear_price = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rightbundle")
        .find(".single-tyre-price")
        .text()
        .replace(/,/g, "");

      if (rear_qty == 1) {
        jQuery(this)
          .parents(".bundleBox")
          .find(".rightbundle")
          .find(".set2price")
          .css("visibility", "hidden");
      } else {
        jQuery(this)
          .parents(".bundleBox")
          .find(".rightbundle")
          .find(".set2price")
          .css("visibility", "visible");
      }

      rear_price = rear_price.replace("AED", "");
      rear_price = parseFloat(rear_price);
      var rearTotal = rear_qty * rear_price;
      rearTotal = parseFloat(rearTotal).toFixed(2);

      var discountqty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .data("discountqty");
      var totalqty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .data("totalqty");
      var selected_qty = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .val();
      var ruleDiscountRear = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .data("rule-discount");

      var formatedRearTotal = formatPriceWithComma(rearTotal);

      jQuery(this)
        .parents(".bundleBox")
        .find(".rightbundle")
        .find(".setfourprice")
        .html(formatedRearTotal);
      jQuery(this)
        .parents(".bundleBox")
        .find(".rightbundle")
        .find(".setOf")
        .html("طقم من " + rear_qty + ":");
      var unitprice = jQuery(this)
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .parent()
        .find(".rear-qty")
        .data("unitprice");
      // if(jQuery(this).hasClass('rear-qty')){
      if (discountqty != undefined && discountqty != undefined) {
        var module_qty = selected_qty % totalqty;
        var discounted_qty = selected_qty - module_qty;
        var pricefor_discount_item =
          (discounted_qty * unitprice * discountqty) / 100; //get percenatge
        var offer_price_with_deducted_ammount =
          discounted_qty * unitprice - pricefor_discount_item;
        var pricefor_without_discount_item = unitprice * module_qty;
        var rear_chargebl_price =
          offer_price_with_deducted_ammount + pricefor_without_discount_item;
        rearTotal = rear_chargebl_price;
        rearTotal = parseFloat(rearTotal).toFixed(2);
        var actual_price = unitprice * selected_qty;
        actual_price = parseFloat(actual_price).toFixed(2);

        var formatedActualPrice = formatPriceWithComma(actual_price);
        var formatedRearTotal = formatPriceWithComma(rearTotal);

        if (selected_qty >= 4 && offer_price_with_deducted_ammount > 0) {
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".setfourprice")
            .html(formatedActualPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .addClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .show();
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set2price .set2")
            .hide();
        } else {
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .removeClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".setfourprice")
            .html(formatedRearTotal);
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .hide();
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set2price .set2")
            .show();
        }
        jQuery(this)
          .parents(".bundleBox")
          .find(".rightbundle")
          .find(".set2price .setfourprice")
          .html(formatedRearTotal);
      }

      if (ruleDiscountRear != undefined) {
        var setof_price = unitprice * selected_qty;
        setof_price = parseFloat(setof_price).toFixed(2);
        var actual_price = unitprice * selected_qty;
        actual_price = parseFloat(actual_price).toFixed(2);

        var formatedActualPrice = formatPriceWithComma(actual_price);
        var formatedSetofPrice = formatPriceWithComma(setof_price);
        var formatedRearTotal = formatPriceWithComma(rearTotal);

        if (selected_qty >= 4) {
          var ruleDiscountPrice = actual_price - ruleDiscountRear;
          rearTotal = parseFloat(ruleDiscountPrice).toFixed(2);
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .addClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".setfourprice")
            .html(formatedActualPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .show();
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set2price .set2")
            .hide();
        } else {
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .removeClass("strike-price");
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".setfourprice")
            .html(formatedSetofPrice);
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .hide();
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set2price .set2")
            .show();
        }
        jQuery(this)
          .parents(".bundleBox")
          .find(".rightbundle")
          .find(".set2price .setfourprice")
          .html(formatedRearTotal);
      }

      if (discountqty == undefined && discountqty == undefined) {
        if (selected_qty == 4) {
          jQuery(this)
            .parents(".bundleBox")
            .find(".rightbundle")
            .find(".set4price")
            .hide();
          if (ruleDiscountRear != undefined) {
            jQuery(this)
              .parents(".bundleBox")
              .find(".rightbundle")
              .find(".set4price")
              .show();
          }
        } else {
          if (selected_qty == 1) {
            jQuery(this)
              .parents(".bundleBox")
              .find(".rightbundle")
              .find(".set1price")
              .hide();
            if (ruleDiscountRear != undefined) {
              jQuery(this)
                .parents(".bundleBox")
                .find(".rightbundle")
                .find(".set1price")
                .show();
            }
          }
        }
      }
      //}

      frontTotal = parseFloat(frontTotal);
      rearTotal = parseFloat(rearTotal);
      var finalBundleTotal = frontTotal + rearTotal;
      finalBundleTotal = parseFloat(finalBundleTotal).toFixed(2);
      var total_qty = parseInt(front_qty) + parseInt(rear_qty);

      var formatedFinalBundleTotal = formatPriceWithComma(finalBundleTotal);

      jQuery(this)
        .parents(".bundleBox")
        .find(".bundle-price-fr-rr")
        .html(formatedFinalBundleTotal);
      jQuery(this)
        .parents(".bundleBox")
        .find(".bundle-total-qty")
        .html("طقم من " + total_qty + ":");

      var isTabbyAvailableForBundle = jQuery(this).data("istabby");
      if (
        parseFloat(finalBundleTotal) >= tabbyfourday &&
        parseFloat(finalBundleTotal) <= tabbyinstallmentmaxvalue &&
        isTabbyAvailableForBundle == "Yes"
      ) {
        jQuery(this)
          .parents(".bundleBox")
          .find(".tabbyinstaller")
          .removeClass("hidetabbyinstaller");
      } else {
        jQuery(this)
          .parents(".bundleBox")
          .find(".tabbyinstaller")
          .addClass("hidetabbyinstaller");
      }
    }
  );

  function formatPriceWithComma(finalPrice) {
    var formatedPrice = finalPrice.toString().split(".");
    formatedPrice[0] = formatedPrice[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    formatedPrice = formatedPrice.join(".");
    return formatedPrice;
  }

  $("#categoryid").change(function () {
    var categoryid = $(this).val();
    jQuery.ajax({
      url: getCategoryWidthUrl,
      type: "POST",
      data: {
        format: "json",
        categoryid: categoryid,
      },
      error: function () {
        alert("Error");
      },
      success: function (data) {
        if (data.status == "SUCCESS") {
          jQuery("#tyre_finder_modal_wrapper .tyre_finder_allcontent").remove();
          jQuery("#tyre_finder_modal_wrapper").append(
            '<ul class="tyre_finder_allcontent  tyre_finder_modal_front_width_content"></ul>'
          );
          jQuery(
            "#tyre_finder_modal_wrapper .tyre_finder_modal_front_width_content"
          ).html(data.response);
          jQuery(".tyre_finder_modal_rear_width_wrapper").html(
            data.rearresponse
          );
        }
      },
    });

    resetFinderSearchForm(); // Reset search form
  });

  $(document).on("click", ".reartyrelink", function () {
    jQuery(".searchloader").show();
    jQuery(".tyre_finder_allcontent").hide();
    jQuery(
      ".tyre_finder_modal_rear_width_content,.tyre_finder_modal_rear_height_content,.tyre_finder_modal_rear_rim_content"
    ).remove();
    jQuery("#tyre_finder_modal_wrapper").append(
      '<ul class="tyre_finder_allcontent  tyre_finder_modal_rear_width_content"></ul>'
    );
    jQuery(
      "#tyre_finder_modal_wrapper .tyre_finder_modal_rear_width_content"
    ).append(jQuery(".tyre_finder_modal_rear_width_wrapper").html());
    jQuery(".tyre_finder_modal_rear_width_content").show();

    jQuery(".allback").hide();
    jQuery(".backfromrim").show();

    jQuery(".stepper li").removeClass("active");
    jQuery(".stepper li").eq(0).addClass("active");

    jQuery(".tyreValue .valuetxt").removeClass("active");
    jQuery(".tyreValue .tvalue_1").addClass("active");

    jQuery(".tyretext .txtcolor").removeClass("active");
    jQuery(".tyretext .txtcolor").eq(0).addClass("active");
    jQuery(".selectiontitle").text(FrontTyreSelectionTxt);
    jQuery(".rear-tyre-selection").show();
    jQuery(".searchloader").hide();
  });

  $(".nav-item").on("click", function () {
    $(".nav-item").removeClass("size");
    $(".nav-item").removeClass("vehicle");
    $(".nav-link").removeClass("active");

    $(this).find(".nav-link").addClass("active");

    if ($(this).hasClass("sizetab")) {
      $(".nav-item").addClass("size");
    } else {
      $(".nav-item").addClass("vehicle");
    }
  });
});

//  Search by Size Start
function getheight(widthValue, label, type) {
  jQuery("#frontWidthLabel-hidden").val(label);
  jQuery("#frontWidthValue-hidden").val(widthValue);
  jQuery(".frontWidthLabel").text(label);
  jQuery(".searchloader").show();
  jQuery.ajax({
    url: getHeightUrl,
    type: "POST",
    data: {
      format: "json",
      type: type,
      width: widthValue,
    },
    error: function () {
      alert("Error");
    },
    success: function (data) {
      if (data.status == "SUCCESS") {
        /* jQuery('.tyre_finder_allcontent').hide();
                        jQuery('#tyre_finder_modal_wrapper ul.tyre_finder_modal_front_height_content').remove();
                        jQuery('#tyre_finder_modal_wrapper').append('<ul class="tyre_finder_allcontent  tyre_finder_modal_front_height_content"></ul>');
                        jQuery('#tyre_finder_modal_wrapper .tyre_finder_modal_front_height_content').html(data.response);
                        jQuery('.frontHeightLabel').removeClass('disablesearchoption');

                        jQuery('.allback').hide();
                        jQuery('.backwidth').show();

                        jQuery('.reset-size-selection').show();

                        jQuery('.stepper li').removeClass('active');
                        jQuery('.stepper li').eq(1).addClass('active');

                        jQuery('.tyreValue .tvalue_1').text(label);
                        jQuery('.tyreValue .valuetxt').removeClass('active');
                        jQuery('.tyreValue .tvalue_2').addClass('active');
                        jQuery('.tyreValue .tvalue_3').addClass('active');

                        jQuery('.tyretext .txtcolor').removeClass('active');
                        jQuery('.tyretext .txtcolor').eq(1).addClass('active');
                        
                        var selectiondata=jQuery('#tyre_finder_modal .selectiondata').text();
                        if(selectiondata != '') {
                            jQuery('#tyre_finder_modal .selectiondata').text(jQuery('#tyre_finder_modal .selectiondata').text()+' / '+label);    
                        }else{
                            jQuery('#tyre_finder_modal .selectiondata').text(label);    
                        }

                        jQuery('.searchloader').hide(); */

        jQuery(".allback").hide();
        jQuery(".backwidth").show();
        jQuery(".reset-size-selection").show();
        jQuery(".front-width_sub-block").hide();
        jQuery(".front-height_sub-block").show();
        jQuery(".front-height_sub-block ul").html(data.response);
        jQuery(".frontHeight").removeClass("disablesearchoption");
        var selectiondata = jQuery("#tyre-selection .selectiondata").text();
        if (selectiondata != "") {
          jQuery("#tyre-selection .selectiondata").text(
            jQuery("#tyre-selection .selectiondata").text() + " / " + label
          );
        } else {
          jQuery("#tyre-selection .selectiondata").text(label);
        }
        jQuery("#tyre-selection .steps .width-step").addClass("active");
        jQuery("#tyre-selection .search-selection-wrap .search-title h4").text(
          HeightTxt
        );
        jQuery(".searchloader").hide();
        jQuery(".tyre-size img").attr("src", $tyreImg205);
      }
    },
  });
}

function getrim(heightValue, label, type) {
  jQuery("#frontHeightLabel-hidden").val(label);
  jQuery("#frontHeightValue-hidden").val(heightValue);
  jQuery(".frontHeightLabel").text(label);
  jQuery(".searchloader").show();
  var widthValue = jQuery("#frontWidthValue-hidden").val();

  jQuery.ajax({
    url: getRimUrl,
    type: "POST",
    data: {
      format: "json",
      type: type,
      width: widthValue,
      height: heightValue,
    },
    error: function () {
      alert("Error");
    },
    success: function (data) {
      if (data.status == "SUCCESS") {
        /* jQuery('.tyre_finder_allcontent').hide();
                           jQuery('#tyre_finder_modal_wrapper ul.tyre_finder_modal_front_rim_content').remove();
                           jQuery('#tyre_finder_modal_wrapper').append('<ul class="tyre_finder_allcontent  tyre_finder_modal_front_rim_content"></ul>');
                           jQuery('#tyre_finder_modal_wrapper .tyre_finder_modal_front_rim_content').html(data.response);

                           jQuery('.frontRimLabel').removeClass('disablesearchoption');

                           jQuery('.allback').hide();
                           jQuery('.backprofile').show();

                           jQuery('.stepper li').removeClass('active');
                           jQuery('.stepper li').eq(2).addClass('active');

                           jQuery('.tyreValue .tvalue_3').text(label);
                           jQuery('.tyreValue .valuetxt').removeClass('active');
                           jQuery('.tyreValue .tvalue_4').addClass('active');

                           jQuery('.tyretext .txtcolor').removeClass('active');
                           jQuery('.tyretext .txtcolor').eq(2).addClass('active');
                            var selectiondata=jQuery('.selectiondata').text();
                            if(selectiondata != '') {
                                jQuery('#tyre_finder_modal .selectiondata').text(jQuery('#tyre_finder_modal .selectiondata').text()+' / '+label);    
                            }else{
                                jQuery('#tyre_finder_modal .selectiondata').text(label);    
                            }

                            jQuery('.searchloader').hide(); */
        jQuery(".allback").hide();
        jQuery(".backprofile").show();
        jQuery(".front-width_sub-block, .front-height_sub-block").hide();
        jQuery(".front-rim_sub-block").show();
        jQuery(".front-rim_sub-block ul").html(data.response);
        jQuery(".frontRim").removeClass("disablesearchoption");
        var selectiondata = jQuery(".selectiondata").text();
        if (selectiondata != "") {
          jQuery("#tyre-selection .selectiondata").text(
            jQuery("#tyre-selection .selectiondata").text() + " / " + label
          );
        } else {
          jQuery("#tyre-selection .selectiondata").text(label);
        }

        jQuery("#tyre-selection .steps .height-step").addClass("active");
        jQuery("#tyre-selection .search-selection-wrap .search-title h4").text(
          RimTxt
        );
        jQuery(".searchloader").hide();
        jQuery(".tyre-size img").attr("src", $tyreImg55);
      }
    },
  });
}

function getRearheight(rearwidthValue, label, type) {
  jQuery("#rearWidthLabel-hidden").val(label);
  jQuery("#rearWidthValue-hidden").val(rearwidthValue);
  jQuery("#rearWidthLabel-text").val(label);
  jQuery(".searchloader").show();
  jQuery.ajax({
    url: getHeightUrl,
    type: "POST",
    data: {
      format: "json",
      type: type,
      width: rearwidthValue,
    },
    error: function () {
      alert("Error");
    },
    success: function (data) {
      if (data.status == "SUCCESS") {
        /* jQuery('.tyre_finder_allcontent').hide();
                        jQuery('#tyre_finder_modal_wrapper ul.tyre_finder_modal_rear_height_content').remove();
                        jQuery('#tyre_finder_modal_wrapper').append('<ul class="tyre_finder_allcontent  tyre_finder_modal_rear_height_content"></ul>');
                        jQuery('#tyre_finder_modal_wrapper .tyre_finder_modal_rear_height_content').html(data.response);
                        jQuery('.allback').hide();
                        jQuery('.rearbackwidth').show();

                        jQuery('.stepper li').removeClass('active');
                        jQuery('.stepper li').eq(1).addClass('active');

                        jQuery('.tyreValue .tvalue_1').text(label);
                        jQuery('.tyreValue .valuetxt').removeClass('active');
                        jQuery('.tyreValue .tvalue_2').addClass('active');
                        jQuery('.tyreValue .tvalue_3').addClass('active');

                        jQuery('.tyretext .txtcolor').removeClass('active');
                        jQuery('.tyretext .txtcolor').eq(1).addClass('active');
                        var rearselectiondata=jQuery('.rearselectiondata').text();
                        if(rearselectiondata != '') {
                            jQuery('.rearselectiondata').text(jQuery('.rearselectiondata').text()+' / '+label);    
                        }else{
                            jQuery('.rearselectiondata').text(label);    
                        }

                        jQuery('.searchloader').hide(); */
        jQuery("#tyre-selection .rearselectiondata").text("");
        jQuery(".tyre_finder_allcontent").hide();
        jQuery(".allback").hide();
        jQuery(".rearbackwidth").show();
        jQuery(".reset-size-selection").show();
        jQuery(".rear-height_sub-block").show();
        jQuery(".rear-height_sub-block ul").html(data.response);
        jQuery(".rear-tyre-selection").show();
        var rearselectiondata = jQuery(
          "#tyre-selection .rearselectiondata"
        ).text();
        if (rearselectiondata != "") {
          jQuery("#tyre-selection .rearselectiondata").text(
            jQuery("#tyre-selection .rearselectiondata").text() + " / " + label
          );
        } else {
          jQuery("#tyre-selection .rearselectiondata").text(label);
        }
        jQuery("#tyre-selection .steps .width-step").addClass("active");
        jQuery(".searchloader").hide();
      }
    },
  });
}

function getRearrim(rearheightValue, label, type) {
  jQuery("#rearHeightLabel-hidden").val(label);
  jQuery("#rearHeightValue-hidden").val(rearheightValue);
  jQuery("#rearHeightLabel-text").val(label);
  jQuery(".searchloader").show();
  var rearwidthValue = jQuery("#rearWidthValue-hidden").val();

  jQuery.ajax({
    url: getRimUrl,
    type: "POST",
    data: {
      format: "json",
      type: type,
      width: rearwidthValue,
      height: rearheightValue,
    },
    error: function () {
      alert("Error");
    },
    success: function (data) {
      if (data.status == "SUCCESS") {
        /*  jQuery('.tyre_finder_allcontent').hide();
                           jQuery('#tyre_finder_modal_wrapper ul.tyre_finder_modal_rear_rim_content').remove();
                           jQuery('#tyre_finder_modal_wrapper').append('<ul class="tyre_finder_allcontent  tyre_finder_modal_rear_rim_content"></ul>');
                           jQuery('#tyre_finder_modal_wrapper .tyre_finder_modal_rear_rim_content').html(data.response);

                           jQuery('.allback').hide();
                           jQuery('.rearbackprofile').show();

                           jQuery('.stepper li').removeClass('active');
                           jQuery('.stepper li').eq(2).addClass('active');

                           jQuery('.tyreValue .tvalue_3').text(label);
                           jQuery('.tyreValue .valuetxt').removeClass('active');
                           jQuery('.tyreValue .tvalue_4').addClass('active');

                           jQuery('.tyretext .txtcolor').removeClass('active');
                           jQuery('.tyretext .txtcolor').eq(2).addClass('active');
                           var rearselectiondata=jQuery('.rearselectiondata').text();
                            if(rearselectiondata != '') {
                                jQuery('.rearselectiondata').text(jQuery('.rearselectiondata').text()+' / '+label);    
                            }else{
                                jQuery('.rearselectiondata').text(label);    
                            }

                            jQuery('.searchloader').hide(); */

        jQuery(".tyre_finder_allcontent").hide();
        jQuery(".allback").hide();
        jQuery(".rearbackprofile").show();
        jQuery(".rear-rim_sub-block").show();
        jQuery(".rear-rim_sub-block ul").html(data.response);
        var rearselectiondata = jQuery(
          "#tyre-selection .rearselectiondata"
        ).text();
        if (rearselectiondata != "") {
          jQuery("#tyre-selection .rearselectiondata").text(
            jQuery("#tyre-selection .rearselectiondata").text() + " / " + label
          );
        } else {
          jQuery("#tyre-selection .rearselectiondata").text(label);
        }
        jQuery("#tyre-selection .steps .height-step").addClass("active");
        jQuery(".searchloader").hide();
      }
    },
  });
}

function selectRim(rimValue, label) {
  /* jQuery('.searchloader').show();
    jQuery("#frontRimLabel-hidden").val(rimValue);
    jQuery(".frontRimLabel").text(label);
    jQuery('.tyre_finder_allcontent').hide();
    jQuery('#tyre_finder_modal_wrapper ul.tyre_finder_modal_submittyre').remove();
    jQuery('#tyre_finder_modal_wrapper').append('<ul class="tyre_finder_allcontent tyre_finder_modal_submittyre"></ul>');
    jQuery('#tyre_finder_modal_wrapper .tyre_finder_modal_submittyre').html(jQuery('#tyre_finder_modal_front_submittyreselection').html());

    jQuery('.allback').hide();
    jQuery('.backrim').show();

    jQuery('.stepper li').addClass('active');

    jQuery('.tyreValue .tvalue_4').text('R'+label);
    jQuery('.tyreValue .valuetxt').addClass('active');

    jQuery('.tyretext .txtcolor').addClass('active');
    var selectiondata=jQuery('#tyre_finder_modal .selectiondata').text();
    if(selectiondata != '') {
        jQuery('#tyre_finder_modal .selectiondata').text(jQuery('#tyre_finder_modal .selectiondata').text()+' R '+label);    
    }else{
        jQuery('#tyre_finder_modal .selectiondata').text(label);    
    }
    
    if(jQuery('.rear-tyre-selection').is(':visible')) { 
        var widthLabel = jQuery(".frontWidthLabel").text();
        var profileLabel = jQuery(".frontHeightLabel").text();
        jQuery('.selectiondata').text('');
        jQuery(".selectiondata").text(widthLabel+' / '+profileLabel+' / '+label);       
    }
    
    jQuery('.searchloader').hide(); */
  jQuery(".allback").hide();
  jQuery(".backrim").show();
  jQuery(".searchloader").show();
  jQuery(".frontRimLabel").removeAttr("disabled");
  jQuery("#frontRimValue-hidden").val(rimValue);
  jQuery("#frontRimLabel-hidden").val(label);
  jQuery(".frontRimLabel").text(label);
  jQuery(
    ".front-width_sub-block, .front-height_sub-block, .front-rim_sub-block"
  ).hide();
  jQuery("#tyre-selection .confirm_sub-block").show();

  var selectiondata = jQuery("#tyre-selection .selectiondata").text();
  if (selectiondata != "") {
    jQuery("#tyre-selection .selectiondata").text(
      jQuery("#tyre-selection .selectiondata").text() + " R " + label
    );
  } else {
    jQuery("#tyre-selection .selectiondata").text(label);
  }

  if (jQuery(".rear-tyre-selection").is(":visible")) {
    var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
    var profileLabel = jQuery(".frontHeightLabel").eq(0).text();
    jQuery("#tyre-selection .selectiondata").text("");
    jQuery("#tyre-selection .selectiondata").text(
      widthLabel + " / " + profileLabel + " / " + label
    );
    jQuery(".backrim").hide();
    jQuery(".backfromrim").show();
  }
  jQuery("#tyre-selection .steps .rim-step").addClass("active");

  //if(jQuery("#is_rear_checked").prop('checked') == true){
  /* if(jQuery("#is_rear_checked").prop('checked') == true || jQuery("#is_rear_checked_mobile").prop('checked') == true){
        var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
        var profileLabel = jQuery(".frontHeightLabel").eq(0).text();
        jQuery("#tyre-selection .crnt-or-frnt").html(jQuery("#tyre-selection .crnt-or-frnt").html().replace("Current Selection", FrontTyreSelectionTxt));
        jQuery('#tyre-selection .selectiondata').text('');
        jQuery("#tyre-selection .selectiondata").text(widthLabel+' / '+profileLabel+' R'+label);
    jQuery('#tyre-selection .tyre_finder_allcontent').hide();
    jQuery('#tyre-selection .rear-width_sub-block').show();
    }else{
    jQuery('#rearWidthValue-hidden').val('');
    jQuery('#rearHeightValue-hidden').val('');
    jQuery('#rearRimValue-hidden').val('');
  } */
  jQuery(".confirm_sub-block .different-rear").show();
  jQuery("#is_rear_checked").prop("checked", false);
  jQuery("#is_rear_checked_mobile").prop("checked", false);
  jQuery("#is_rear_checked2").prop("checked", false);

  jQuery("#rearWidthLabel-hidden").val("");
  jQuery("#rearWidthValue-hidden").val("");
  jQuery("#rearHeightLabel-hidden").val("");
  jQuery("#rearHeightValue-hidden").val("");
  jQuery("#rearRimLabel-hidden").val("");
  jQuery("#rearRimValue-hidden").val("");
  jQuery("#tyre-selection .rearselectiondata").text("");

  jQuery(".searchloader").hide();
  jQuery(".tyre-size img").attr("src", $tyreImg16);
}

function selectRearRim(rimValue, label) {
  jQuery(".searchloader").show();
  jQuery("#rearRimValue-hidden").val(rimValue);
  jQuery("#rearRimLabel-hidden").val(label);
  jQuery("#rearRimLabel-text").val(label);
  jQuery(".tyre_finder_allcontent").hide();
  jQuery(".allback").hide();
  jQuery(".rearbackrim").show();
  jQuery(".stepper li").addClass("active");
  jQuery(".tyreValue .tvalue_4").text("R" + label);
  jQuery(".tyreValue .valuetxt").addClass("active");
  jQuery(".tyretext .txtcolor").addClass("active");
  var rearselectiondata = jQuery(".rearselectiondata").text();
  if (rearselectiondata != "") {
    jQuery(".rearselectiondata").text(
      jQuery(".rearselectiondata").text() + " R" + label
    );
  } else {
    jQuery(".rearselectiondata").text(label);
  }
  jQuery("#tyre-selection .confirm_sub-block").show();
  jQuery(".searchloader").hide();
  jQuery("#tyre-selection .steps .rim-step").addClass("active");
  jQuery(".confirm_sub-block .different-rear").hide();
}

function submitTyreSelection() {
  jQuery(".searchloader").show();
  jQuery("#finder-size-form").submit();
}
function submitVehicleSelection() {
  var catId = jQuery("#finder-vehicle-form").find(".search_cat_id").val();
  if (catId == brakesCatId || catId == batteryCatId || catId == filtersCatId) {
    submitVehicleParts();
  } else if (catId == oilChangeCatId || catId == "servicepacks") {
    submitOilChange();
  } else {
    jQuery("#finder-vehicle-form").submit();
  }
}

function resetFinderSearchForm() {
  jQuery(".searchloader").show();
  jQuery(".frontWidthLabel").text("Width");
  jQuery("#frontWidthLabel-hidden").val("");
  jQuery("#frontWidthValue-hidden").val("");

  jQuery(".frontHeightLabel").text("Height");
  jQuery("#frontHeightLabel-hidden").val("");
  jQuery("#frontHeightValue-hidden").val("");

  jQuery(".frontRimLabel").text("Rim");
  jQuery("#frontRimLabel-hidden").val("");
  jQuery("#frontRimValue-hidden").val("");

  jQuery("#rearWidthLabel-hidden").val("");
  jQuery("#rearWidthValue-hidden").val("");
  jQuery("#rearHeightLabel-hidden").val("");
  jQuery("#rearHeightValue-hidden").val("");
  jQuery("#rearRimLabel-hidden").val("");
  jQuery("#rearRimValue-hidden").val("");
  jQuery("#tyre-selection .selectiondata").text("");
  jQuery("#tyre-selection .rearselectiondata").text("");
  jQuery("#tyre-selection .tyre_finder_allcontent").hide();
  jQuery("#tyre-selection .front-width_sub-block").show();
  jQuery("#tyre-selection .steps ul li").removeClass("active");
  jQuery(".searchloader").hide();
  jQuery("#tyre-selection .reset-size-selection").hide();
  jQuery("#tyre-selection .allback").hide();
  jQuery(".tyre-size img").attr("src", $tyreImgNormal);

  jQuery("#is_rear_checked").prop("checked", false);
  jQuery("#is_rear_checked_mobile").prop("checked", false);
  jQuery("#is_rear_checked2").prop("checked", false);
}

// Search by size popup end

//  Search by Vehicle popup start
function getMakeList() {
  jQuery(".searchloader").show();
  jQuery.ajax({
    type: "POST",
    url: getMakeUrl,
    success: function (data) {
      jQuery(".brand_sub-block ul").html(data.response);
      jQuery(".searchloader").hide();
    },
  });
}
function getmodel(value, label) {
  jQuery("#vehicle_make").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_make").text(
    label
  );
  jQuery("#vehicle_make_hidden").val(value);
  jQuery(".searchloader").show();
  jQuery(".brand_sub-block ul").attr("id", "");
  jQuery(".vehiclemodel_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");

  jQuery.ajax({
    type: "POST",
    url: getModelUrl,
    data: "make=" + value,
    success: function (data) {
      /* jQuery('.vehicle_finder_modal_allcontent').hide();
                   jQuery('#vehicle_finder_modal_wrapper ul.vehicle_finder_modal_model_content').remove();
                   jQuery('#vehicle_finder_modal_wrapper').append('<ul class="vehicle_finder_modal_allcontent  vehicle_finder_modal_model_content"></ul>');
                   jQuery('#vehicle_finder_modal_wrapper .vehicle_finder_modal_model_content').html(data.response);

                   jQuery('.vehicleallbackbutton').hide();
                   jQuery('.vehiclebackmake').show();

                    jQuery('.stepper li').removeClass('active');
                    jQuery('.stepper li.model').addClass('active');

                    jQuery('.reset-vehicle-selection').show();
                    var vehicleselectiondata=jQuery('#vehicle_finder_modal .vehicleselectiondata').text();
                    if(vehicleselectiondata != '') {
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(jQuery('#vehicle_finder_modal .vehicleselectiondata').text()+' / '+label);    
                    }else{
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(label);    
                    }

                    jQuery('.searchloader').hide(); */

      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackmake").show();
      jQuery("#vehicle-selection .steps li").removeClass("active");
      jQuery("#vehicle-selection .steps .make-step").addClass("active");
      jQuery(".reset-vehicle-selection").show();
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(".brand_sub-block").hide();
      jQuery(".vehiclemodel_sub-block").show();
      jQuery(".vehiclemodel_sub-block ul").html(data.response);
      jQuery(".vehicle_model").removeClass("disablesearchoption");
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        ModelTxt
      );
    },
  });
}

function getyear(value, label) {
  var model = value;
  var make = jQuery("#vehicle_make_hidden").val();
  jQuery("#vehicle_model").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_model").text(
    label
  );
  jQuery("#vehicle_model_hidden").val(value);
  jQuery(".searchloader").show();
  jQuery(".vehiclemodel_sub-block ul").attr("id", "");
  jQuery(".vehicleyear_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");

  jQuery.ajax({
    type: "POST",
    url: getyearUrl,
    data: { make: make, model: model },
    success: function (data) {
      /* jQuery('.vehicle_finder_modal_allcontent').hide();
                   jQuery('#vehicle_finder_modal_wrapper ul.vehicle_finder_modal_year_content').remove();
                   jQuery('#vehicle_finder_modal_wrapper').append('<ul class="vehicle_finder_modal_allcontent  vehicle_finder_modal_year_content"></ul>');
                   jQuery('#vehicle_finder_modal_wrapper .vehicle_finder_modal_year_content').html(data.response);

                   jQuery('.vehicleallbackbutton').hide();
                   jQuery('.vehiclebackmodel').show();

                    jQuery('.stepper li.model').removeClass('active');
                    jQuery('.stepper li.year').addClass('active');                   

                    var vehicleselectiondata=jQuery('#vehicle_finder_modal .vehicleselectiondata').text();
                    if(vehicleselectiondata != '') {
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(jQuery('#vehicle_finder_modal .vehicleselectiondata').text()+' / '+label);    
                    }else{
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(label);    
                    }

                    jQuery('.searchloader').hide(); */
      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackmodel").show();
      jQuery("#vehicle-selection .steps .model-step").addClass("active");
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(".brand_sub-block, .vehiclemodel_sub-block").hide();
      jQuery(".vehicleyear_sub-block").show();
      jQuery(".vehicleyear_sub-block ul").html(data.response);
      jQuery(".vehicle_year").removeClass("disablesearchoption");
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        YearTxt
      );
    },
  });
}

var enginesTyre = "";
function getengine(value, label) {
  var make = jQuery("#vehicle_make_hidden").val();
  var model = jQuery("#vehicle_model_hidden").val();
  var year = value;
  jQuery(".searchloader").show();
  jQuery(".vehicleyear_sub-block ul").attr("id", "");
  jQuery(".vehicleengine_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");

  jQuery("#vehicle_year").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_year").text(
    label
  );
  jQuery("#vehicle_year_hidden").val(value);

  jQuery.ajax({
    type: "POST",
    url: getengineUrl,
    data: { make: make, model: model, year: year },
    success: function (data) {
      /* jQuery('.vehicle_finder_modal_allcontent').hide();
                   jQuery('#vehicle_finder_modal_wrapper ul.vehicle_finder_modal_engine_content').remove();
                   jQuery('#vehicle_finder_modal_wrapper').append('<ul class="vehicle_finder_modal_allcontent  vehicle_finder_modal_engine_content"></ul>');
                   jQuery('#vehicle_finder_modal_wrapper .vehicle_finder_modal_engine_content').html(data.engineHtml);
                   enginesTyre=data.enginesTyre;

                   jQuery('.vehicleallbackbutton').hide();
                   jQuery('.vehiclebackyear').show();

                    jQuery('.stepper li.year').removeClass('active');
                    jQuery('.stepper li.engine').addClass('active'); 

                    var vehicleselectiondata=jQuery('#vehicle_finder_modal .vehicleselectiondata').text();
                    if(vehicleselectiondata != '') {
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(jQuery('#vehicle_finder_modal .vehicleselectiondata').text()+' / '+label);    
                    }else{
                        jQuery('#vehicle_finder_modal .vehicleselectiondata').text(label);    
                    } */

      /* Start unique tyre sizes */
      // console.log(data.enginesTyre);
      //var enginesTyre=data.enginesTyre.toString();
      var enginesTyre = data.enginesTyre.join("");
      jQuery("#vehicle_engine_hidden").val(label);
      jQuery(".searchloader").show();
      /* jQuery('#input-search').val('');
          jQuery('.vehicle_finder_modal_engine_content').attr('id','');
          jQuery('.vehicle_finder_modal_allcontent').hide();
          jQuery('#vehicle_finder_modal_wrapper ul.vehicle_finder_modal_tyresize_content').remove();
          jQuery('#vehicle_finder_modal_wrapper').append('<ul class="vehicle_finder_modal_allcontent  vehicle_finder_modal_tyresize_content" id="searchUL"></ul>');

          jQuery('#vehicle_finder_modal_wrapper .vehicle_finder_modal_tyresize_content').html(enginesTyre);
          jQuery(".vehicle_finder_modal_allcontent.vehicle_finder_modal_tyresize_content").append("<li id='last-note'><span>Note: Most vehicle manufacturer’s produce vehicles with more than one possible size.  We strongly recommend all customers check the tyre size printed on the side wall of their tyres before purchase.</span></li>");
          jQuery('.vehicleallbackbutton').hide();
          jQuery('.vehiclebackyear').show();
          jQuery('.stepper li.year').removeClass('active');
                    jQuery('.stepper li.engine').addClass('active'); 
          var vehicleselectiondata=jQuery('#vehicle_finder_modal .vehicleselectiondata').text();
          if(vehicleselectiondata != '') {
            jQuery('#vehicle_finder_modal .vehicleselectiondata').text(jQuery('#vehicle_finder_modal .vehicleselectiondata').text()+' / '+label);    
          }else{
            jQuery('#vehicle_finder_modal .vehicleselectiondata').text(label);    
          } */
      /* End unique tyre sizes */

      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackyear").show();
      jQuery("#vehicle-selection .steps .year-step").addClass("active");
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(
        ".brand_sub-block, .vehiclemodel_sub-block, .vehicleyear_sub-block"
      ).hide();
      jQuery(".vehicleengine_sub-block").show();
      jQuery(".vehicleengine_sub-block ul").html(enginesTyre);
      jQuery(".vehicleengine_sub-block ul").append(
        "<li id='last-note'><span>" +
          VehicleFinderLastPopupNote +
          "</span></li>"
      );
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        TyreSizeTxt
      );
    },
  });
}
function getenginetyre(value, label) {
  jQuery("#vehicle_engine_hidden").val(label);
  jQuery(".searchloader").show();
  var tyresize = enginesTyre[value];
  jQuery(".vehicle_finder_modal_allcontent").hide();
  jQuery(
    "#vehicle_finder_modal_wrapper ul.vehicle_finder_modal_tyresize_content"
  ).remove();
  jQuery("#vehicle_finder_modal_wrapper").append(
    '<ul class="vehicle_finder_modal_allcontent  vehicle_finder_modal_tyresize_content"></ul>'
  );
  jQuery(
    "#vehicle_finder_modal_wrapper .vehicle_finder_modal_tyresize_content"
  ).html(tyresize);

  jQuery(".vehicleallbackbutton").hide();
  jQuery(".vehiclebackengine").show();
  var vehicleselectiondata = jQuery(
    "#vehicle_finder_modal .vehicleselectiondata"
  ).text();
  if (vehicleselectiondata != "") {
    jQuery("#vehicle_finder_modal .vehicleselectiondata").text(
      jQuery("#vehicle_finder_modal .vehicleselectiondata").text() +
        " / " +
        label
    );
  } else {
    jQuery("#vehicle_finder_modal .vehicleselectiondata").text(label);
  }

  jQuery(".searchloader").hide();
}
function getmodifications(value, label) {
  var make = jQuery("#vehicle_make_hidden").val();
  var makeLabel = jQuery("#vehicle_make").text();
  var modelLabel = jQuery("#vehicle_model").text();
  var model = jQuery("#vehicle_model_hidden").val();
  var year = value;
  jQuery(".searchloader").show();
  jQuery(".vehicleyear_sub-block ul").attr("id", "");
  jQuery(".vehiclemodification_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");

  jQuery("#vehicle_year").text(value);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_year").text(
    value
  );
  jQuery("#vehicle_year_hidden").val(value);

  jQuery.ajax({
    type: "POST",
    url: getModificationsUrl,
    data: { make: make, model: model, year: year },
    success: function (data) {
      jQuery("#vehicle_engine_hidden").val(label);
      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackyear").show();
      jQuery("#vehicle-selection .steps .year-step").addClass("active");
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(
        ".brand_sub-block, .vehiclemodel_sub-block, .vehicleyear_sub-block"
      ).hide();
      jQuery(".vehiclemodification_sub-block").show();
      jQuery(".vehiclemodification_sub-block ul").html(data.response);
      // jQuery('.vehiclemodification_sub-block ul').append("<li id='last-note'><span>"+VehicleFinderLastPopupNote+"</span></li>");
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        EngineTxt
      );
    },
  });
}
function getTyreSizes(value, label) {
  var make = jQuery("#vehicle_make_hidden").val();
  var makeLabel = jQuery("#vehicle_make").text();
  var modelLabel = jQuery("#vehicle_model").text();
  var model = jQuery("#vehicle_model_hidden").val();
  var year = jQuery("#vehicle_year_hidden").val();
  var modification = value;
  jQuery(".searchloader").show();
  //jQuery("#vehicle_year").text(label);
  //jQuery("#vehicle_year_hidden").val(value);

  jQuery(".vehiclemodification_sub-block ul").attr("id", "");
  jQuery(".vehicleengine_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");

  jQuery.ajax({
    type: "POST",
    url: getSearchByModelUrl,
    data: { make: make, model: model, year: year, modification: modification },
    success: function (data) {
      jQuery("#vehicle_engine_hidden").val(label);
      jQuery(".searchloader").show();
      jQuery("#input-search").val("");
      jQuery(".vehicle_finder_modal_engine_content").attr("id", "");
      jQuery("#vehicle-selection .steps .engine-step").addClass("active");
      //var tyresize=enginesTyre[value];
      var enginesTyre = data.enginesTyre.join("");
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(
        ".brand_sub-block, .vehiclemodel_sub-block, .vehicleyear_sub-block, .vehiclemodification_sub-block"
      ).hide();
      jQuery(".vehicleengine_sub-block").show();
      jQuery(".vehicleengine_sub-block ul").html(enginesTyre);
      jQuery(".vehicleengine_sub-block ul").append(
        "<li id='last-note'><span>" +
          VehicleFinderLastPopupNote +
          "</span></li>"
      );
      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackengine").show();
      jQuery(".vehiclebackengine").attr(
        "onclick",
        "vehcilegoback('vehiclemodification_sub-block','vehiclebackyear','vehicleyearimage','Engine')"
      );
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        var hasVehicleEngine = jQuery("#vehicle_engine_hidden").val();
        if (hasVehicleEngine) {
          var contentText =
            makeLabel + " / " + modelLabel + " / " + year + " / " + label;
          jQuery("#vehicle-selection .vehicleselectiondata").text(contentText);
        } else {
          jQuery("#vehicle-selection .vehicleselectiondata").text(
            jQuery("#vehicle-selection .vehicleselectiondata").text() +
              " / " +
              label
          );
        }
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        TyreSizeTxt
      );
      jQuery(
        "#vehicle-selection .search-selection-wrap .selection-title .search-by-vehicle-label"
      ).text(TyreSizeTxt);
    },
  });
}
function showproduct(width, height, rim, rear_width, rear_height, rear_rim) {
  jQuery(".searchloader").show();
  jQuery("#frontWidthLabel-hidden").val(width);

  jQuery("#frontHeightLabel-hidden").val(height);

  jQuery("#frontRimLabel-hidden").val(rim);

  if (rear_width != "") {
    jQuery("#rearWidthLabel-hidden").val(rear_width);
  }

  if (rear_height != "") {
    jQuery("#rearHeightLabel-hidden").val(rear_height);
  }

  if (rear_rim != "") {
    jQuery("#rearRimLabel-hidden").val(rear_rim);
  }

  jQuery("#finder-size-form").submit();
  jQuery(".searchloader").hide();
}
function goback(contentname, backbutton, type, searchbytype) {
  jQuery(".searchloader").show();
  jQuery(".tyre_finder_allcontent").hide();
  jQuery("." + contentname).show();

  jQuery(".allback").hide();
  jQuery("." + backbutton).show();

  if (searchbytype == "Width" && type != "rear") {
    jQuery(".allback").hide();
  }

  if (searchbytype == "FrontRim") {
    jQuery(".stepper li").removeClass("active");
    jQuery(".stepper li").eq(2).addClass("active");

    jQuery(".tyreValue .valuetxt").removeClass("active");
    jQuery(".tyreValue .tvalue_4").addClass("active");

    jQuery(".tyretext .txtcolor").removeClass("active");
    jQuery(".tyretext .txtcolor").eq(2).addClass("active");

    var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
    var profileLabel = jQuery(".frontHeightLabel").eq(0).text();
    jQuery(".data-selected").text(widthLabel + " / " + profileLabel);

    jQuery(".rear-tyre-selection").hide();
    jQuery(".rearselectiondata").text("");
  }

  if (searchbytype == "Rim" || searchbytype == "rearRim") {
    jQuery(".stepper li").removeClass("active");
    jQuery(".stepper li").eq(2).addClass("active");

    jQuery(".tyreValue .valuetxt").removeClass("active");
    jQuery(".tyreValue .tvalue_4").addClass("active");

    jQuery(".tyretext .txtcolor").removeClass("active");
    jQuery(".tyretext .txtcolor").eq(2).addClass("active");

    if (jQuery(".rear-tyre-selection").is(":visible")) {
      var rearselectiondata = jQuery(".rearselectiondata").text();
      if (rearselectiondata != "") {
        var widthLabel = jQuery("#rearWidthLabel-text").val();
        var profileLabel = jQuery("#rearHeightLabel-text").val();
        jQuery(".rearselectiondata").text(widthLabel + " / " + profileLabel);
      } else {
        jQuery("#rearwWidthLabel").hide();
        jQuery(".allback").hide();
        jQuery(".backrim").show();
      }
    } else {
      var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
      var profileLabel = jQuery(".frontHeightLabel").eq(0).text();
      jQuery(".data-selected").text(widthLabel + " / " + profileLabel);
    }
    jQuery("#tyre-selection .steps .rim-step").removeClass("active");
    jQuery("#tyre-selection .search-selection-wrap .search-title h4").text(
      RimTxt
    );
    jQuery(".tyre-size img").attr("src", $tyreImg55);
  }

  if (searchbytype == "Height" || searchbytype == "rearHeight") {
    jQuery(".stepper li").removeClass("active");
    jQuery(".stepper li").eq(1).addClass("active");

    jQuery(".tyreValue .valuetxt").removeClass("active");
    jQuery(".tyreValue .tvalue_2").addClass("active");
    jQuery(".tyreValue .tvalue_3").addClass("active");
    jQuery(".tyretext .txtcolor").removeClass("active");
    jQuery(".tyretext .txtcolor").eq(1).addClass("active");
    if (jQuery(".rear-tyre-selection").is(":visible")) {
      var widthLabel = jQuery("#rearWidthLabel-text").val();
      jQuery(".rearselectiondata").text(widthLabel);
    } else {
      var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
      jQuery(".data-selected").text(widthLabel);
    }
    jQuery("#tyre-selection .steps .height-step").removeClass("active");
    jQuery("#tyre-selection .search-selection-wrap .search-title h4").text(
      HeightTxt
    );
    jQuery(".tyre-size img").attr("src", $tyreImg205);
  }

  if (searchbytype == "Width" || searchbytype == "rearWidth") {
    jQuery(".stepper li").removeClass("active");
    jQuery(".stepper li").eq(0).addClass("active");

    jQuery(".tyreValue .valuetxt").removeClass("active");
    jQuery(".tyreValue .tvalue_1").addClass("active");

    jQuery(".tyretext .txtcolor").removeClass("active");
    jQuery(".tyretext .txtcolor").eq(0).addClass("active");
    jQuery(".reset-size-selection").hide();
    if (jQuery(".rear-tyre-selection").is(":visible")) {
      jQuery(".rearselectiondata").text("");
      jQuery(".allback").hide();
      jQuery(".backfromrim").show();
      jQuery(".reset-size-selection").show();
    } else {
      jQuery(".data-selected").text("");
    }
    jQuery("#tyre-selection .steps .width-step").removeClass("active");
    jQuery("#tyre-selection .search-selection-wrap .search-title h4").text(
      WidthTxt
    );
    jQuery(".tyre-size img").attr("src", $tyreImgNormal);
  }
  if (searchbytype == "Engine") {
    alert("Test Dev");
    jQuery(".stepper li.Engine").removeClass("active");
    jQuery(".stepper li.Year").addClass("active");
  }

  if (searchbytype == "Year") {
    jQuery(".stepper li.Engine").removeClass("active");
    jQuery(".stepper li.Make").addClass("active");
  }

  if (searchbytype == "Model") {
    jQuery(".stepper li.Engine").removeClass("active");
    jQuery(".stepper li.Make").addClass("active");
  }

  if (searchbytype == "Make") {
    jQuery(".stepper li.Engine").removeClass("active");
    jQuery(".stepper li.Make").addClass("active");
  }

  jQuery(".searchloader").hide();
}
function resetselection() {
  jQuery(".searchloader").show();
  jQuery(".tyre_finder_allcontent").hide();
  jQuery(".tyre_finder_modal_front_width_content").show();

  jQuery(".allback").hide();

  jQuery(".frontWidthLabel").text("Width");
  jQuery("#frontWidthLabel-hidden").val("");
  jQuery("#frontWidthValue-hidden").val("");

  jQuery(".frontHeightLabel").text("Height");
  jQuery("#frontHeightLabel-hidden").val("");
  jQuery("#frontHeightValue-hidden").val("");

  jQuery(".frontRimLabel").text("Rim");
  jQuery("#frontRimLabel-hidden").val("");
  jQuery("#frontRimValue-hidden").val("");

  jQuery(".reset-size-selection").hide();
  jQuery(".data-selected").text("");
  jQuery(".tyreInfoLeft .tyreinfoResult .tyreselection .selectiontitle").text(
    CurrentTyreSelectionTxt
  );
  jQuery(".tyreInfoLeft .stepper li").removeClass("active");
  jQuery(".tyreInfoLeft .stepper li:first").addClass("active");
  jQuery(
    ".tyreInfoLeft .tyreinfoResult .rear-tyre-selection .rearselectiondata"
  ).text("");
  jQuery(".rear-tyre-selection").hide();
  jQuery(".searchloader").hide();
}
function resetSearchSuggestion() {
  jQuery("#input-search").val("");
  jQuery(".vehicle_finder_modal_allcontent ul li").show();
}
function vehcilegoback(contentname, backbutton, roundimage, searchbytype) {
  resetSearchSuggestion();
  jQuery(".vehicle_finder_modal_allcontent ul").attr("id", "");
  jQuery("." + contentname).attr("id", "searchUL");
  jQuery(".searchloader").show();
  jQuery(".vehicle_finder_modal_allcontent").hide();
  jQuery("." + contentname).show();

  jQuery(".vehicleallbackbutton").hide();
  jQuery("." + backbutton).show();
  jQuery(".searchloader").hide();

  var vehicle_make = jQuery("#vehicle_make").text();
  var vehicle_model = jQuery("#vehicle_model").text();
  var vehicle_year = jQuery("#vehicle_year").text();

  if (
    searchbytype == "Engine" &&
    contentname != "vehiclemodification_sub-block"
  ) {
    jQuery("#vehicle-selection .vehicleselectiondata").text(
      vehicle_make + " / " + vehicle_model + " / " + vehicle_year
    );
    jQuery(".vehiclestepper li").removeClass("active");
    jQuery(".vehiclestepper li").eq(3).addClass("active");
    jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
      "Engine"
    );
  }

  if (
    searchbytype == "Engine" &&
    contentname == "vehiclemodification_sub-block"
  ) {
    jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
      EngineTxt
    );
    jQuery(
      "#vehicle-selection .search-selection-wrap .selection-title .search-by-vehicle-label"
    ).text(EngineTxt);
    jQuery("#vehicle-selection .steps .engine-step").removeClass("active");
    jQuery("#vehicle-selection .vehicleselectiondata").text(
      vehicle_make + " / " + vehicle_model + " / " + vehicle_year
    );
  }

  if (searchbytype == "Year") {
    jQuery("#vehicle-selection .vehicleselectiondata").text(
      vehicle_make + " / " + vehicle_model
    );
    jQuery(".vehiclestepper li").removeClass("active");
    jQuery(".vehiclestepper li").eq(2).addClass("active");
    jQuery("#vehicle-selection .steps .year-step").removeClass("active");
    var searchType = jQuery("#finder-vehicle-form").find(".search_type").val();
    if (searchType == "vehicle-engine-search") {
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        "Engine"
      );
    } else {
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        YearTxt
      );
    }
  }

  if (searchbytype == "Model") {
    jQuery("#vehicle-selection .vehicleselectiondata").text(vehicle_make);
    jQuery(".vehiclestepper li").removeClass("active");
    jQuery(".vehiclestepper li").eq(1).addClass("active");
    jQuery("#vehicle-selection .steps .model-step").removeClass("active");
    jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
      ModelTxt
    );
  }
  if (searchbytype == "Make") {
    jQuery("#vehicle-selection .vehicleselectiondata").text("");
    jQuery(".vehiclestepper li").removeClass("active");
    jQuery(".vehiclestepper li").eq(0).addClass("active");
    jQuery("#vehicle-selection .steps .make-step").removeClass("active");
    jQuery(".reset-vehicle-selection").hide();
    jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
      MakeTxt
    );
  }
}
function resetvehicleselection() {
  jQuery(".searchloader").show();
  jQuery(".vehicle_finder_modal_allcontent").hide();
  jQuery(".brand_sub-block").show();
  jQuery(".vehicleallbackbutton").hide();
  jQuery("#vehicle_make").text("Make");
  jQuery("#vehicle_make_hidden").val("");

  jQuery("#vehicle_model").text("Model");
  jQuery("#vehicle_model-hidden").val("");

  jQuery("#vehicle_year").text("Year");
  jQuery("#vehicle_year-hidden").val("");

  jQuery(".reset-vehicle-selection").hide();
  jQuery("#vehicle-selection .vehicleselectiondata").text("");
  jQuery("#vehicle-selection .steps li").removeClass("active");
  jQuery(".searchloader").hide();
  jQuery("#input-search").val("");
  jQuery(".vehicle_finder_modal_allcontent ul li").show();
  jQuery(".brand_sub-block ul").attr("id", "searchUL");
}
// Start To prevent on click search direct redirection without selecting options
jQuery("#finder-size-form").submit(function (e) {
  if (
    jQuery(".confirm_sub-block #is_rear_checked2").prop("checked") ||
    jQuery("#is_rear_checked_mobile").prop("checked")
  ) {
    jQuery("<input>")
      .attr({ type: "hidden", name: "product_list_limit", value: 500 })
      .appendTo("#finder-size-form");
  }
  e.preventDefault(e);
  /* start keep the brand and year filter as it is in the url */
  //document.getElementById('finder-size-form').action = 'somethingelse';
  urlString = window.location.href;
  let paramString = urlString.split("?")[1];
  let queryString = new URLSearchParams(paramString);
  for (let pair of queryString.entries()) {
    if (pair[0].includes("mgs_brand") || pair[0].includes("year")) {
      //console.log("Key is: " + pair[0]);
      //console.log("Value is: " + pair[1]);
      jQuery("<input>")
        .attr({ type: "hidden", name: pair[0], value: pair[1] })
        .appendTo("#finder-size-form");
    }
  }
  /* end keep the brand and year filter as it is in the url */
  var frontWidth = jQuery("#frontWidthLabel-hidden").val();
  var frontHeight = jQuery("#frontHeightLabel-hidden").val();
  var frontRim = jQuery("#frontRimLabel-hidden").val();
  if (frontWidth && frontHeight && frontRim) {
    jQuery(this).unbind("submit").submit();
  } else {
    jQuery(".frontWidthLabel").trigger("click");
    jQuery(".searchloader").hide();
  }
});

jQuery("#finder-vehicle-form").submit(function (e) {
  e.preventDefault(e);
  var vehicleMake = jQuery("#vehicle_make_hidden").val();
  var vehicleModel = jQuery("#vehicle_model_hidden").val();
  var vehicleYear = jQuery("#vehicle_year_hidden").val();
  if (vehicleMake && vehicleModel && vehicleYear) {
    jQuery(this).unbind("submit").submit();
  } else {
    jQuery("#vehicle_make").trigger("click");
  }
});
// End To prevent on click search direct redirection without selecting options

/* Start tyre finder search functionality */
jQuery(".icon[data-search-tab]").click(function (e) {
  e.preventDefault();
  jQuery(".search-wrap").css("display", "none");
  var searchTab = jQuery(this).attr("data-search-tab");
  jQuery("#" + searchTab).css("display", "block", jQuery(this).data());
});

//Search by Size
jQuery(function () {
  jQuery(".search-size-button .icon").hover(
    function () {
      jQuery(this).parent(".search-size-button").addClass("btAnimate");
    },
    function () {
      jQuery(this).parent(".search-size-button").removeClass("btAnimate");
    }
  );

  jQuery(".search-size-button .icon").hover(function (event) {
    if (jQuery(this).parent(".search-size-button").hasClass("btAnimate")) {
      jQuery(".search-size-button span").text(SearchbyVehicleTxt);
    } else {
      jQuery(".search-size-button span").text(SearchbySizeTxt);
    }
  });
});

// Search by Vehicle
jQuery(function () {
  jQuery(".search-vehicle-button .icon").hover(
    function () {
      jQuery(this).parent(".search-vehicle-button").addClass("btAnimate");
    },
    function () {
      jQuery(this).parent(".search-vehicle-button").removeClass("btAnimate");
    }
  );
  jQuery(".search-vehicle-button .icon").hover(function (event) {
    var autopartsPageType = jQuery("#autoparts_page_type").val();
    //if(!autopartsPageType){
    if (autopartsPageType == 0) {
      if (jQuery(this).parent(".search-vehicle-button").hasClass("btAnimate")) {
        jQuery(".search-vehicle-button span").text(SearchbySizeTxt);
      } else {
        jQuery(".search-vehicle-button span").text(SearchbyVehicleTxt);
      }
    } else {
      if (jQuery(this).parent(".search-vehicle-button").hasClass("btAnimate")) {
        var spanText = jQuery(this).siblings("span").text();
        if (spanText == SearchbyCodeTxt) {
          jQuery(".search-vehicle-button span").text(SearchbyVehicleTxt);
        } else {
          jQuery(".search-vehicle-button span").text(SearchbyCodeTxt);
        }
      } else {
        if (
          jQuery(".search-by-code").is(":visible") ||
          !jQuery(".search-by-code").hasClass("hide")
        ) {
          if (
            jQuery(this).parent(".search-vehicle-button").hasClass("btAnimate")
          ) {
            jQuery(".search-vehicle-button span").text(SearchbyVehicleTxt);
          } else {
            jQuery(".search-vehicle-button span").text(SearchbyCodeTxt);
          }
        } else {
          jQuery(".search-vehicle-button span").text(SearchbyVehicleTxt);
        }
      }
    }
  });

  jQuery(".frontWidth, .frontHeight, .frontRim").click(function (event) {
    /* start get all width */
    var allWidthOptions_length = jQuery("#allWidthOptions li").length;
    var allRearWidthOptions_length = jQuery("#allRearWidthOptions li").length;
    if (allWidthOptions_length <= 0 || allRearWidthOptions_length <= 0) {
      jQuery(".searchloader").show();
      jQuery.ajax({
        url: getWidthOptionsUrl,
        type: "POST",
        data: {
          format: "json",
        },
        error: function () {
          alert("Error");
        },
        success: function (data) {
          if (data.status == "success") {
            jQuery("#allWidthOptions").html(data.fronthtml);
            jQuery("#allRearWidthOptions").html(data.rearhtml);
            jQuery(".searchloader").hide();
          } else {
          }
        },
      });
      jQuery.ajax({
        url: getTyreSizeUrl,
        type: "POST",
        data: {
          format: "json",
        },
        error: function () {
          alert("Error");
        },
        success: function (data) {
          jQuery(".tyre-size-search-selection .select2-tyre-size-search").html(
            data.response
          );
        },
      });
    }
    /* end get all width*/
    jQuery("#vehicle-selection").fadeOut();
    jQuery("#tyre-selection").fadeIn();
    if (jQuery("#tyre-selection .front-width_sub-block").is(":visible")) {
      jQuery(".front-width_sub-block").show();
    }
    if (jQuery("#tyre-selection .front-height_sub-block").is(":visible")) {
      jQuery(".front-height_sub-block").show();
    }
    if (jQuery("#tyre-selection .front-rim_sub-block").is(":visible")) {
      jQuery(".front-rim_sub-block").show();
    }
    if (jQuery("#tyre-selection .confirm_sub-block").is(":visible")) {
      jQuery("#tyre-selection .confirm_sub-block").show();
    }
    jQuery("html").addClass("scroll-overflow");
    jQuery("html, body").animate({ scrollTop: 0 }, 0);
    jQuery(".search-overlay").addClass("open");
  });

  jQuery(".vehicle_make, .vehicle_model, .vehicle_year").click(function (
    event
  ) {
    jQuery("#vehicle-selection").fadeIn();
    if (jQuery("#vehicle-selection .brand_sub-block").is(":visible")) {
      jQuery(".brand_sub-block").show();
      var searchType = jQuery(this)
        .closest("#finder-vehicle-form")
        .find(".search_type")
        .val();
      var searchCatId = jQuery(this)
        .closest("#finder-vehicle-form")
        .find(".search_cat_id")
        .val();
      if (searchType == "vehicle-engine-search") {
        getTechDocMakeList(searchCatId);
        jQuery("#vehicle-selection .steps .year-step span.text").text(
          EngineTxt
        );
        jQuery("#vehicle_year").text(EngineTxt);
      } else {
        getMakeList();
        //jQuery('#vehicle-selection .steps .year-step span.text').text('Year');
        //jQuery("#vehicle_year").text('Year');
      }
    }
    if (jQuery("#vehicle-selection .vehiclemodel_sub-block").is(":visible")) {
      jQuery(".vehiclemodel_sub-block").show();
    }
    if (jQuery("#vehicle-selection .vehicleyear_sub-block").is(":visible")) {
      jQuery(".vehicleyear_sub-block").show();
    }
    if (jQuery("#vehicle-selection .vehicleengine_sub-block").is(":visible")) {
      jQuery(".vehicleengine_sub-block").show();
    }
    if (jQuery("#vehicle-selection .confirm_sub-block").is(":visible")) {
      jQuery("#vehicle-selection .oilchange_confirm_sub-block").hide();
      jQuery("#vehicle-selection .confirm_sub-block").show();
    }
    if (
      jQuery("#vehicle-selection .oilchange_confirm_sub-block").is(":visible")
    ) {
      jQuery("#vehicle-selection .confirm_sub-block").hide();
      jQuery("#vehicle-selection .oilchange_confirm_sub-block").show();
    }
    jQuery("html").addClass("scroll-overflow");
    jQuery("html, body").animate({ scrollTop: 0 }, 0);
    jQuery(".search-overlay").addClass("open");
  });

  jQuery(".search-close").click(function (event) {
    jQuery(this).parents(".search-selection").fadeOut();
    jQuery("html").removeClass("scroll-overflow");
    jQuery(".search-overlay").removeClass("open");
  });
  jQuery(".add-vehicle").click(function (event) {
    jQuery(".search-size-button .icon").trigger("mouseenter").trigger("click");
    jQuery(".vehicle_make").trigger("click");
  });
});
/* End tyre finder search functionality */

/* Start add vehicle functionality */

jQuery(document).on("click", ".top-add-vehicle", function () {
  if (jQuery("#add-vehicle-find").has("option").length == 1) {
    getAddTechDocMakeList(oilChangeCatId);
  }
});

function getAddTechDocMakeList(searchCatId) {
  jQuery.ajax({
    type: "POST",
    url: techDocMakeUrl,
    data: "search_cat_id=" + searchCatId,
    success: function (data) {
      jQuery("#add-vehicle-find").html(data.optionresponse);
    },
  });
}

var isDbVehicleList = jQuery("#add-vehicle-modal #is_db_vehiclelist").val();
jQuery(document).on("change", "#add-vehicle-find", function () {
  var callUrl = techDocModelUrl;
  var selected_make = jQuery(this).val();
  var makeName = jQuery(this).find("option:selected").text();
  jQuery.ajax({
    type: "POST",
    url: callUrl,
    //data: "manuId=" + selected_make,
    data: { manuId: selected_make, is_db_vehiclelist: isDbVehicleList },
    success: function (resultData) {
      var responseData = resultData.response;
      if (isDbVehicleList == 1) {
        responseData = resultData.modelresponse;
      }
      jQuery('select[name="add_vehicle_model"]').html(responseData);
      jQuery("#add-vehicle-model-find").prop("disabled", false);
      jQuery("#add_vehicle_make_label").val(makeName);
    },
  });
});

jQuery(document).on("change", "#add-vehicle-model-find", function () {
  var callUrl = techDocEngineUrl;
  var modelId = jQuery(this).val();
  var manuId = jQuery("#add-vehicle-find").find(":selected").val();
  if (manuId != "") {
    var manuId = jQuery("#add-vehicle-find").find(":selected").val();
  } else {
    // add vehicle popup fix
    var manuId = jQuery(
      '#add-vehicle-modal #form-vehicle-add select[name="add_vehicle_make"]'
    )
      .find("option:selected")
      .val();
  }
  var modelname = jQuery(this).find("option:selected").text();
  jQuery.ajax({
    type: "POST",
    url: callUrl,
    data: {
      manuId: manuId,
      modelId: modelId,
      is_db_vehiclelist: isDbVehicleList,
    },
    success: function (resultData) {
      var responseData = resultData.response;
      if (isDbVehicleList == 1) {
        responseData = resultData.engineresponse;
      }
      jQuery('select[name="add_vehicle_engine"]').html(responseData);
      jQuery("#vehicle_model_mob").val(modelId);
      jQuery("#add-vehicle-engine-find").prop("disabled", false);
      jQuery("#add_vehicle_model_label").val(modelname);
    },
  });
});

jQuery(document).on("change", "#add-vehicle-engine-find", function () {
  var engineName = jQuery(this).find("option:selected").text();
  jQuery("#add_vehicle_engine_label").val(engineName);
});
/* start add vehicle from login popup */
jQuery(document).on("click", ".login-plus-vehicle", function () {
  if (jQuery("#login-vehicle-select").has("option").length == 1) {
    getAddTechDocMakeListForLogin(oilChangeCatId);
  }
});

function getAddTechDocMakeListForLogin(searchCatId) {
  jQuery.ajax({
    type: "POST",
    url: techDocMakeUrl,
    data: "search_cat_id=" + searchCatId,
    success: function (data) {
      jQuery("#login-vehicle-select").html(data.optionresponse);
    },
  });
}

var isDbVehicleListForLogin = jQuery(
  "#social-login-authentication #is_db_vehiclelist_login"
).val();
jQuery(document).on("change", "#login-vehicle-select", function () {
  var callUrl = techDocModelUrl;
  var selected_make = jQuery(this).val();
  var makeName = jQuery(this).find("option:selected").text();
  jQuery.ajax({
    type: "POST",
    url: callUrl,
    //data: "manuId=" + selected_make,
    data: { manuId: selected_make, is_db_vehiclelist: isDbVehicleList },
    success: function (resultData) {
      var responseData = resultData.response;
      if (isDbVehicleList == 1) {
        responseData = resultData.modelresponse;
      }
      jQuery('select[name="add_vehicle_model"]').html(responseData);
      jQuery("#login-model-select").prop("disabled", false);
      jQuery("#add_vehicle_make_label_login").val(makeName);
    },
  });
});

jQuery(document).on("change", "#login-model-select", function () {
  var callUrl = techDocEngineUrl;
  var modelId = jQuery(this).val();
  var manuId = jQuery("#login-vehicle-select").find(":selected").val();
  if (manuId != "") {
    var manuId = jQuery("#login-vehicle-select").find(":selected").val();
  } else {
    // add vehicle popup fix
    var manuId = jQuery('#social-form-login select[name="add_vehicle_make"]')
      .find("option:selected")
      .val();
  }
  var modelname = jQuery(this).find("option:selected").text();
  jQuery.ajax({
    type: "POST",
    url: callUrl,
    data: {
      manuId: manuId,
      modelId: modelId,
      is_db_vehiclelist: isDbVehicleList,
    },
    success: function (resultData) {
      var responseData = resultData.response;
      if (isDbVehicleList == 1) {
        responseData = resultData.engineresponse;
      }
      jQuery('select[name="add_vehicle_engine"]').html(responseData);
      jQuery("#vehicle_model_mob").val(modelId);
      jQuery("#login-engine-select").prop("disabled", false);
      jQuery("#add_vehicle_model_label_login").val(modelname);
    },
  });
});

jQuery(document).on("change", "#login-engine-select", function () {
  var engineName = jQuery(this).find("option:selected").text();
  jQuery("#add_vehicle_engine_label_login").val(engineName);
});
/* end add vehicle from login popup */
/* End add vehicle functionality */

function getTechDocMakeList(searchCatId) {
  jQuery(".searchloader").show();
  jQuery.ajax({
    type: "POST",
    url: techDocMakeUrl,
    data: "search_cat_id=" + searchCatId,
    success: function (data) {
      jQuery(".brand_sub-block ul").html(data.response);
      jQuery(".searchloader").hide();
    },
  });
}
function getTechdocModel(value, label, slug) {
  jQuery("#vehicle_make").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_make").text(
    label
  );
  jQuery("#vehicle_make_hidden").val(value);
  jQuery("#add_vehicle_make_label").val(label);
  jQuery("#add-vehicle-find").removeAttr("disabled");
  jQuery("#add-vehicle-find").find("option:selected").removeAttr("disabled");
  jQuery("#add-vehicle-find").find("option:selected").val(value);
  jQuery("#add-vehicle-find").find("option:selected").text(label);
  jQuery(".searchloader").show();
  var searchCatId = jQuery("#finder-vehicle-form").find(".search_cat_id").val();
  jQuery(".brand_sub-block ul").attr("id", "");
  jQuery(".vehiclemodel_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");
  jQuery.ajax({
    type: "POST",
    url: techDocModelUrl,
    data: { manuId: value, cat_id: searchCatId },
    success: function (data) {
      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackmake").show();
      jQuery("#vehicle-selection .steps li").removeClass("active");
      jQuery("#vehicle-selection .steps .make-step").addClass("active");
      jQuery(".reset-vehicle-selection").show();
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(".brand_sub-block").hide();
      jQuery(".vehiclemodel_sub-block").show();
      jQuery(".vehiclemodel_sub-block ul").html(data.listresponse);
      jQuery(".vehicle_model").removeClass("disablesearchoption");
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        ModelTxt
      );
      jQuery(".searchloader").hide();
    },
  });
}
function getTechdocEngine(value, label, modelname) {
  var model = value;
  var make = jQuery("#vehicle_make_hidden").val();
  jQuery("#vehicle_model").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_model").text(
    label
  );
  jQuery("#vehicle_model_hidden").val(value);
  jQuery("#add-vehicle-model-find").removeAttr("disabled");
  jQuery("#add-vehicle-model-find")
    .find("option:selected")
    .removeAttr("disabled");
  jQuery("#add-vehicle-model-find").find("option:selected").val(value);
  jQuery("#add-vehicle-model-find").find("option:selected").text(label);
  jQuery("#add_vehicle_model_label").val(label);
  jQuery(".searchloader").show();
  jQuery("#vehicle_year").text("Engine");
  var searchCatId = jQuery("#finder-vehicle-form").find(".search_cat_id").val();
  jQuery(".vehiclemodel_sub-block ul").attr("id", "");
  jQuery(".vehicleyear_sub-block ul").attr("id", "searchUL");
  jQuery("#input-search").val("");
  jQuery.ajax({
    type: "POST",
    url: techDocEngineUrl,
    data: { manuId: make, modelId: model, cat_id: searchCatId },
    success: function (data) {
      jQuery(".vehicleallbackbutton").hide();
      jQuery(".vehiclebackmodel").show();
      jQuery("#vehicle-selection .steps .model-step").addClass("active");
      jQuery(".vehicle_finder_modal_allcontent").hide();
      jQuery(".brand_sub-block, .vehiclemodel_sub-block").hide();
      jQuery(".vehicleyear_sub-block").show();
      jQuery(".vehicleyear_sub-block ul").html(data.listresponse);
      jQuery(".vehicle_year").removeClass("disablesearchoption");
      var vehicleselectiondata = jQuery(
        "#vehicle-selection .vehicleselectiondata"
      ).text();
      if (vehicleselectiondata != "") {
        jQuery("#vehicle-selection .vehicleselectiondata").text(
          jQuery("#vehicle-selection .vehicleselectiondata").text() +
            " / " +
            label
        );
      } else {
        jQuery("#vehicle-selection .vehicleselectiondata").text(label);
      }
      jQuery(".searchloader").hide();
      jQuery("#vehicle-selection .search-selection-wrap .search-title h4").text(
        "Engine"
      );
    },
  });
}

function getTechdocEngineSelectValue(value, label) {
  jQuery("#vehicle_year").text(label);
  jQuery(".mobile-tyre-search-finder #search-by-vehicle #vehicle_year").text(
    label
  );
  jQuery("#vehicle_engine_hidden").val(value);
  jQuery("#vehicle_year_hidden").val(value);
  jQuery("#add-vehicle-engine-find").removeAttr("disabled");
  jQuery("#add-vehicle-engine-find")
    .find("option:selected")
    .removeAttr("disabled");
  jQuery("#add-vehicle-engine-find").find("option:selected").val(value);
  jQuery("#add-vehicle-engine-find").find("option:selected").text(label);
  jQuery("#add_vehicle_engine_label").val(label);
  jQuery(".vehicleallbackbutton").hide();
  jQuery(".vehiclebackyear").show();
  jQuery("#vehicle-selection .steps .year-step").addClass("active");
  jQuery(".vehicle_finder_modal_allcontent").hide();
  var vehicleselectiondata = jQuery(
    "#vehicle-selection .vehicleselectiondata"
  ).text();
  if (vehicleselectiondata != "") {
    jQuery("#vehicle-selection .vehicleselectiondata").text(
      jQuery("#vehicle-selection .vehicleselectiondata").text() + " / " + label
    );
  } else {
    jQuery("#vehicle-selection .vehicleselectiondata").text(label);
  }
  jQuery("#vehicle-selection .oilchange_confirm_sub-block").show();
  jQuery("#input-search").val("");
}

function submitOilChange() {
  var make = jQuery("#vehicle_make_hidden").val();
  var model = jQuery("#vehicle_model_hidden").val();
  var engine = jQuery("#vehicle_engine_hidden").val();
  var makeLabel = jQuery("#add_vehicle_make_label").val();
  var modelLabel = jQuery("#add_vehicle_model_label").val();
  var engineLabel = jQuery("#add_vehicle_engine_label").val();
  if (make != "" && model != "" && engine != "") {
    /* add vehicle info */
    jQuery.ajax({
      type: "POST",
      url: addVehicleUrl,
      async: false,
      data: {
        add_vehicle_make: make,
        add_vehicle_make_label: makeLabel,
        add_vehicle_model: model,
        add_vehicle_model_label: modelLabel,
        add_vehicle_engine: engine,
        add_vehicle_engine_label: engineLabel,
        is_ajax: 1,
      },
      success: function (resultData) {
        return true;
      },
    });
    /* end add vehicle info */
    jQuery("#form-vehicle-add").submit();
  } else {
    jQuery(".vehicle_year").trigger("click");
  }
}
function submitVehicleParts() {
  var catId = jQuery("#finder-vehicle-form").find(".search_cat_id").val();
  var formUrl = autopartsSearchUrl;

  var make = jQuery("#vehicle_make_hidden").val();
  var model = jQuery("#vehicle_model_hidden").val();
  var engine = jQuery("#vehicle_engine_hidden").val();
  var makeLabel = jQuery("#add_vehicle_make_label").val();
  var modelLabel = jQuery("#add_vehicle_model_label").val();
  var engineLabel = jQuery("#add_vehicle_engine_label").val();

  if (catId == brakesCatId || catId == batteryCatId || catId == filtersCatId) {
    var prevslug = jQuery("#vehicle_model_slug").val();

    var slugString = jQuery("#vehicle_engine_slug").val();
    var newSlug = "";
    var queryUrl = "";
    var cateid = "";
    if (make != "" && model != "" && engine != "") {
      if (catId == brakesCatId) {
        var cateid = 100626;
        var queryUrl =
          "?menuid=" +
          make +
          "&modelid=" +
          model +
          "&carid=" +
          engine +
          "&partscateid=" +
          cateid +
          "&categoryid=" +
          catId;
        var formUrl = formUrl + "brakes.html" + queryUrl;
      }
      if (catId == oilChangeCatId) {
        var cateid = 101994;
        var queryUrl =
          "?menuid=" +
          make +
          "&modelid=" +
          model +
          "&carid=" +
          engine +
          "&partscateid=" +
          cateid +
          "&categoryid=" +
          catId;
        var formUrl = formUrl + "lubricants.html" + queryUrl;
      }
      if (catId == batteryCatId) {
        var cateid = 100042;
        var queryUrl =
          "?menuid=" +
          make +
          "&modelid=" +
          model +
          "&carid=" +
          engine +
          "&partscateid=" +
          cateid +
          "&categoryid=" +
          catId;
        var formUrl = formUrl + "battery.html" + queryUrl;
      }
      if (catId == filtersCatId) {
        var cateid = 100005;
        var queryUrl =
          "?menuid=" +
          make +
          "&modelid=" +
          model +
          "&carid=" +
          engine +
          "&partscateid=" +
          cateid +
          "&categoryid=" +
          catId;
        var formUrl = formUrl + "filters.html" + queryUrl;
      }
    }
    /* add vehicle info */
    if (make != "" && model != "" && engine != "") {
      jQuery.ajax({
        type: "POST",
        url: addVehicleUrl,
        async: false,
        data: {
          add_vehicle_make: make,
          add_vehicle_make_label: makeLabel,
          add_vehicle_model: model,
          add_vehicle_model_label: modelLabel,
          add_vehicle_engine: engine,
          add_vehicle_engine_label: engineLabel,
          is_ajax: 1,
        },
        success: function (resultData) {
          return true;
        },
      });
    }
    /* end add vehicle info */
    jQuery("#finder-vehicle-form").attr("action", formUrl).submit();
  }
}

/* Mobile tyrefinder toggle */
jQuery(".services-mobile .tyre-category").click(function () {
  jQuery(".mobile-tyre-search-finder").toggle();
  jQuery(".services-mobile").toggle();
  jQuery(".offer-services-cover").toggleClass("position-active");
  //jQuery('.offer-services-cover').toggleClass('viewport-height');
  jQuery(".cms-home").removeClass("overflow-hidden-custom");
  //jQuery('.offers-banner-mobile .offer-slider').trigger('stop.owl.autoplay');
});
jQuery(".mobile-tyre-search-finder .tabs-nav ul li").click(function () {
  jQuery(".mobile-tyre-search-finder .tabs-nav ul li").removeClass("active");
  jQuery(".mobile-tyre-search-finder .tabs-nav ul li").removeClass("disabled");
  jQuery(this).toggleClass("active");
  jQuery(this).toggleClass("disabled");
  jQuery(".mobile-tyre-search-finder .search-form.search-wrap").toggle();
});
jQuery(".mobile-tyre-search-finder .close-searchpopup").click(function () {
  jQuery(".mobile-tyre-search-finder").toggle();
  jQuery(".services-mobile").toggle();
  jQuery(".offer-services-cover").toggleClass("position-active");
  //jQuery('.offer-services-cover').toggleClass('viewport-height');
  //jQuery('.offers-banner-mobile .offer-slider').trigger('play.owl.autoplay',[3000]);
});

/* Start Tyre size colored Images on hover */
jQuery(".frontWidth").hover(
  function () {
    jQuery(".tyre-size").addClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImg205);
  },
  function () {
    jQuery(".tyre-size").removeClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImgNormal);
  }
);

jQuery(".frontHeight").hover(
  function () {
    jQuery(".tyre-size").addClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImg55);
  },
  function () {
    jQuery(".tyre-size").removeClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImgNormal);
  }
);

jQuery(".frontRim").hover(
  function () {
    jQuery(".tyre-size").addClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImg16);
  },
  function () {
    jQuery(".tyre-size").removeClass("width-hover");
    jQuery(".tyre-size img").attr("src", $tyreImgNormal);
  }
);
/* End Tyre size colored Images on hover */

// Home banner service icon hide show
jQuery(".services-list .row").each(function () {
  var max = 5;
  if (jQuery(this).find(".col").length > max) {
    jQuery(".toggle-icon").click(function () {
      jQuery(this)
        .siblings(":gt(" + max + ")")
        .toggleClass("hide");
      jQuery(".offer-services-cover").toggleClass("viewport-height");
      if (jQuery(".show_more").length) {
        jQuery(this).html(
          '<span class="show_less"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M23.5 294.5l152-143.1C180.1 146.2 186.1 144 192 144s11.88 2.188 16.5 6.562l152 143.1c9.625 9.125 10.03 24.31 .9375 33.93c-9.125 9.688-24.38 10.03-33.94 .9375l-135.5-128.4l-135.5 128.4c-9.562 9.094-24.75 8.75-33.94-.9375C13.47 318.9 13.87 303.7 23.5 294.5z"/></svg></span>'
        );
      } else {
        jQuery(this).html(
          '<span class="show_more"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M360.5 217.5l-152 143.1C203.9 365.8 197.9 368 192 368s-11.88-2.188-16.5-6.562L23.5 217.5C13.87 208.3 13.47 193.1 22.56 183.5C31.69 173.8 46.94 173.5 56.5 182.6L192 310.9l135.5-128.4c9.562-9.094 24.75-8.75 33.94 .9375C370.5 193.1 370.1 208.3 360.5 217.5z"/></svg></span>'
        );
      }
    });
  }
});

/* filter auto search */
jQuery(".filter-option-search input").on("keyup", function () {
  var value = jQuery(this).val().toLowerCase();
  jQuery(jQuery(this).closest(".filter-options-content").find(".item")).filter(
    function () {
      jQuery(this).toggle(
        jQuery(this).text().toLowerCase().indexOf(value) > -1
      );
    }
  );
});
/* filter auto search */

/*Filter hide show on icon click*/
jQuery(".selection-search-toggle").click(function (event) {
  jQuery(".selection-search").toggle();
});

jQuery(".size, .vehicle").click(function (event) {
  jQuery(".selection-search").hide();
});

jQuery("#reset-vehicle-submit").click(function (event) {
  jQuery("#add-vehicle-find").val("").trigger("change");
  jQuery("#add-vehicle-model-find").val("").trigger("change");
  jQuery("#add-vehicle-engine-find").val("").trigger("change");
});
jQuery(".bicycle-tyres-search .search-class a").click(function (event) {
  var formUrl = jQuery(this).attr("data-cat-url");
  jQuery(".bicycle-tyres-search form").attr("action", formUrl);
  jQuery(".bicycle-tyres-search .search-class a").removeClass("toggle-change");
  jQuery(this).addClass("toggle-change");
});
jQuery(".bicycle-tyres-search .search-button").click(function (event) {
  jQuery(".bicycle-tyres-search form").submit();
});
jQuery(".tyre-finder-search .size, .home-shop-tyre").click(function (event) {
  jQuery(".frontWidth").trigger("click");
});
jQuery(".tyre-finder-search .vehicle").click(function (event) {
  jQuery(".vehicle_make").trigger("click");
});
jQuery(".confirm_sub-block #is_rear_checked2").click(function (event) {
  if (jQuery(this).prop("checked")) {
    jQuery("#is_rear_checked").prop("checked", true);
    jQuery("#is_rear_checked_mobile").prop("checked", true);
  } else {
    jQuery("#is_rear_checked").prop("checked", false);
    jQuery("#is_rear_checked_mobile").prop("checked", false);
  }

  if (
    jQuery("#is_rear_checked").prop("checked") == true ||
    jQuery("#is_rear_checked_mobile").prop("checked") == true
  ) {
    var widthLabel = jQuery(".frontWidthLabel").eq(0).text();
    var profileLabel = jQuery(".frontHeightLabel").eq(0).text();
    jQuery("#tyre-selection .crnt-or-frnt").html(
      jQuery("#tyre-selection .crnt-or-frnt")
        .html()
        .replace(CurrentTyreSelectionTxt, FrontTyreSelectionTxt)
    );
    jQuery("#tyre-selection .tyre_finder_allcontent").hide();
    jQuery("#tyre-selection .rear-width_sub-block").show();
  } else {
    jQuery("#rearWidthValue-hidden").val("");
    jQuery("#rearHeightValue-hidden").val("");
    jQuery("#rearRimValue-hidden").val("");
  }
  jQuery(
    "#tyre-selection .search-selection-wrap .tyreselection .selected-step .search-by-size-front-label"
  ).text(WidthTxt);
  jQuery("#tyre-selection .steps .height-step").removeClass("active");
  jQuery("#tyre-selection .steps .rim-step").removeClass("active");
  jQuery("#tyre-selection .steps .width-step").removeClass("active");
});
