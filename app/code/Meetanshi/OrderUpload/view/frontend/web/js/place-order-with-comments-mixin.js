define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (placeOrderAction) {
        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(placeOrderAction, function(originalAction, paymentData, redirectOnSuccess, messageContainer) {
			// adding order comments
			var allowComment = window.checkoutConfig.orderupload.allowComment;
            if(allowComment) // true
            {
                var order_comments=jQuery('[name="comment-code"]').val();
                if (order_comments !== null) {
                    paymentData.additional_data = {comments: order_comments};
                }
            }
            return originalAction(paymentData, redirectOnSuccess, messageContainer);
        });
    };
});
