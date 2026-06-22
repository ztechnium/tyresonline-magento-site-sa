define(
    [
        'jquery',
        'mage/validation'
    ],
    function ($) {
        'use strict';

        return {

            /**
             * Validate checkout agreements
             *
             * @returns {Boolean}
             */
            validate: function () {
                var emailValidationResult = false;
                var email = $('form[data-role=email-with-possible-login]').val();
                if(~email.search("gmail.com")){ //should use Regular expression in real store.
                    emailValidationResult = true;
                }
                return emailValidationResult;
            }
        };
    }
);