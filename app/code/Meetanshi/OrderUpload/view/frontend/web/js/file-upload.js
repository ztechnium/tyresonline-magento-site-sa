define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'jquery/ui',
    'jquery/file-uploader',
    'mage/translate'
], function ($, mageTemplate, alert) {
    'use strict';

    $.widget("meetanshi.meetanshiFileUpload", {
        itemsCount: 0,
        itemIndex: 0,
        linkTitle: '',
        options: {},
        _create: function () {
            this.itemTemplate = mageTemplate(this.options.itemTemplate);
            var allowedSize = $(this.options.allowedSize).html();
            var maxFileSize = $(this.options.maxFileSize).html();
            var extArray = allowedSize.split(',');
            var j = 0;
            this.linkTitle = $(this.options.addLink).html();
            this.element.fileupload({
                add: function (e, data) {
                    var uploadErrors = [];
                    var ext = data.originalFiles[j].name.split('.').pop().toLowerCase();
                    if ($.inArray(ext, extArray) == -1) {
                        uploadErrors.push($.mage.__('The file you attached is not valid, try again with a valid file type attachment.'));
                    }
                    if (data.originalFiles[j].size > (maxFileSize * 1024 * 1024)) {//2 MB
                        uploadErrors.push($.mage.__('Filesize is too big'));
                    }
                    if (uploadErrors.length > 0) {
                        data.files[j] = '';
                        alert({
                            title: 'Error',
                            content: uploadErrors.join("\n"),
                            actions: {
                                always: function () {
                                }
                            }
                        });
                    } else {
                        data.submit();
                    }
                    j++;
                    if (j >= data.originalFiles.length || data.originalFiles.length == 1){
                        j=0;
                    }
                },
                start: function (e) {
                    $('.fileupload-loader').show();
                },
                dataType: 'json',
                done: $.proxy(this.onUpload, this)
            });
            this._bind();
        },
        destroy: function () {
            this.element.fileupload('destroy');
            this._unbind();
        },
        _bind: function () {
            $(this.options.itemsContainer).on('click', this.options.removeLinks, $.proxy(this.onRemoveClick, this));
        },
        _unbind: function () {
            $(this.options.itemsContainer).off('click', this.options.removeLinks);
        },
        onUpload: function (e, data) {
            $('.fileupload-loader').hide();
            if (typeof data['result'] !== "undefined") {
                var result = data['result'];

                if (!result['error']) {
                    for (var i=0; i<data['result'].length; i++){
                        this.addItem(data['result'][i]);
                    }
                } else {
                    this.showError(result['error']);
                }
            }
        },
        onRemoveClick: function (event) {
            var item = $(event.target).closest('li');
            if (item) {
                this.removeItem(item);
            }
            event.preventDefault();
        },
        addItem: function (data) {
            var templateData = {
                'index': this.itemIndex++,
                'file': data.file,
                'fileName': data.name,
                'fileSize': data.text_file_size,
                'currentDate': data.currentDate
            };
            $(this.options.itemsContainer).append(this.itemTemplate(templateData));
            this.itemsCount++;
            this.switchLinkTitle();
            this.updateItemsContainerVisibility();
        },
        removeItem: function (item) {
            item.hide();
            item.find('[data-role=remove]').val(1);
            this.itemsCount--;
            this.switchLinkTitle();
            this.updateItemsContainerVisibility();
        },
        switchLinkTitle: function () {
            var addLink = $(this.options.addLink);
            if (this.itemsCount > 0) {
                addLink.html(addLink.data('switch-title'));
            } else {
                addLink.html(this.linkTitle);
            }
        },
        updateItemsContainerVisibility: function () {
            var itemsContainer = $(this.options.itemsContainer);
            if (this.itemsCount > 0) {
                itemsContainer.show();
            } else {
                itemsContainer.hide();
            }
        },
        showError: function (message) {
            $(this.options.errorContainer)
                .html(message)
                .fadeIn()
                .delay(1000)
                .fadeOut();
        },
    });
    return $.meetanshi.meetanshiFileUpload;
});
