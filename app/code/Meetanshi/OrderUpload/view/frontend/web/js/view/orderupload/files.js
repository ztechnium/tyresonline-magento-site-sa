define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'jquery/file-uploader',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ],
    function ($, ko, Component, jQfileUpload, fullScreenLoader, alert) {
        'use strict';
        var quoteData = window.checkoutConfig.quoteData;
        var mediaPath = window.checkoutConfig.orderupload.mediaPath;
        var tempMediaPath = window.checkoutConfig.orderupload.tempMediaPath;
        var removeUrl = window.checkoutConfig.orderupload.removeUrl;
        var enabledModule = window.checkoutConfig.orderupload.enabledModule;
        var allowedSize = window.checkoutConfig.orderupload.allowedSize;
        var maxFileSize = window.checkoutConfig.orderupload.maxFileSize;
        var allowComment = window.checkoutConfig.orderupload.allowComment;
        var extArray = allowedSize.split(',');
        var form_data = new FormData();
        ko.bindingHandlers.fileUpload = {
            init: function (element, valueAccessor) {
                if (quoteData.file_data) {
                    var fileObj = $.parseJSON(quoteData.file_data);
                    for (var i=0; i<fileObj.length; i++){
                        var cls = 'rmf'+i;
                        var removeText = $.mage.__('Remove');

                        var fileUrl = mediaPath + fileObj[i]['file'];
                        jQuery('div.filePreview').append('<p id="'+cls+'" class="orderupload-link"><a target="_blank" href="' + fileUrl + '">' + fileObj[i]['name'] + '</a><span class="remove-btn inits"><a class="'+cls+'" href="javascript:void(0)" data-value="'+fileObj[i]['name']+'">'+removeText+'</a></span></p>');
                    }
                }
                jQuery('#file_types').text($.mage.__('Allowed file types :') +' '+ allowedSize);

                $('#fileupload').fileupload({
                    add: function (e, data) {
                        var uploadError = [];
                        var ext = data.originalFiles[0].name.split('.').pop().toLowerCase();
                        if ($.inArray(ext, extArray) === -1) {
                            uploadError.push($.mage.__('The file you attached is not valid, try again with a valid file type attachment.'));
                        }
                        if (data.originalFiles[0].size > (maxFileSize * 1024 * 1024)) {//2 MB
                            uploadError.push($.mage.__('Filesize is too big'));
                        }
                    },
                    start: function (e) {
                    },
                    dataType: 'json',
                    url: window.checkoutConfig.orderupload.uploadUrl,
                    done: function (e, data) {}
                });
                $('span.remove-btn.inits > a').on("click",function(e){
                    var fName = $(this).attr("data-value");
                    var rmFileUrl = removeUrl + 'fileName/' + fName;
                    var clss = $(this).attr("class");
                    fullScreenLoader.startLoader();
                    $.ajax({
                        url: rmFileUrl,
                        type: "GET",
                        data: fName,
                        success: function (data) {
                            fullScreenLoader.stopLoader();
                            $('#'+clss).remove();
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            self.addError(thrownError);
                        },
                        cache: false,
                        contentType: false,
                        processData: false
                    });
                });
            },
        };
        return Component.extend({
            defaults: {
                template: 'Meetanshi_OrderUpload/orderupload/files'
            },
            canVisibleFileUpload: enabledModule,
            canAllowComment: allowComment,
            drop: function(data, event) {
                var droppedFiles = event.originalEvent.dataTransfer.files;
                this.uploadFiles(droppedFiles);
            },
            selectFiles: function(data, event) {
                var droppedFiles = event.target.files;
                this.uploadFiles(droppedFiles);
            },
            uploadFiles: function(addedFiles) {
                var errorFlag = true;
                var uploadErrors = [];
                for (var i = 0; i < addedFiles.length; i++) {
                    var ext = addedFiles[i].name.split('.').pop().toLowerCase();
                    if ($.inArray(ext, extArray) === -1) {
                        uploadErrors.push($.mage.__('The file you attached is not valid, try again with a valid file type attachment.'));
                    }
                    if (addedFiles[i].size > (maxFileSize * 1024 * 1024)) {//2 MB
                        uploadErrors.push($.mage.__('Filesize is too big'));
                    }
                    if (uploadErrors.length > 0) {
                        fullScreenLoader.stopLoader();
                        alert({
                            title: 'Error',
                            content: uploadErrors.join("\n"),
                            actions: {
                                always: function () {
                                }
                            }
                        });
                        uploadErrors = [];
                        errorFlag = false;
                        form_data.delete('file[]');
                    } else {
                        form_data.append('file[]', addedFiles[i]);
                    }

                    if (!errorFlag){
                        form_data.delete('file[]');
                    }
                }
                fullScreenLoader.startLoader();
                if (errorFlag) {
                    var removeText = $.mage.__('Remove');
                    $.ajax({
                        url: window.checkoutConfig.orderupload.uploadUrl,
                        type: "POST",
                        data: form_data,
                        success: function (data) {
                            jQuery('.orderupload-link').remove();
                            fullScreenLoader.stopLoader();
                            for (var i = 0; i < data.length; i++) {
                                var cls = 'rnf'+i;
                                var fileUrl = tempMediaPath + 'tmp/orderupload' + data[i].file;
                                jQuery('div.filePreview').append('<p id="'+cls+'" class="orderupload-link"><a href="' + fileUrl + '">' + data[i].name + '</a><span class="remove-btn uploaded"><a class="'+cls+'" href="javascript:void(0)" data-value="'+data[i].name+'">'+removeText+'</a></span></p>');
                            }

                            $('span.remove-btn.uploaded > a').on("click",function(e){
                                var fName = $(this).attr("data-value");
                                var rmFileUrl = removeUrl + 'fileName/' + fName;
                                var clss = $(this).attr("class");
                                fullScreenLoader.startLoader();
                                $.ajax({
                                    url: rmFileUrl,
                                    type: "GET",
                                    data: fName,
                                    success: function (data) {
                                        fullScreenLoader.stopLoader();
                                        $('#'+clss).remove();
                                    },
                                    error: function (xhr, ajaxOptions, thrownError) {
                                        self.addError(thrownError);
                                    },
                                    cache: false,
                                    contentType: false,
                                    processData: false
                                });
                            });
                            form_data.delete('file[]');
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            self.addError(thrownError);
                        },
                        cache: false,
                        contentType: false,
                        processData: false
                    });
                }
            },
        });
    }
);