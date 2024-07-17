define(
    [
        'jquery',
        'Magento_Ui/js/modal/modal'
    ],
    function($) {
        "use strict";
        $.widget('CustomModal.modalHelp', {
            _create: function() {
                this.options.modalOption = this._getModalOptions();
                this._bind();
            },
            _getModalOptions: function() {
                var options;
                options = {
                    type: 'slide',
                    responsive: true,
                    innerScroll: true,
                    title: this.options.modalTitle,
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'confirm-button',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };
                return options;
            },
            _bind: function(){
                var modalOption = this.options.modalOption;
                var modalForm = this.options.modalId;

                $(document).on('click', this.options.modalTarget,  function(){
                    $(modalForm).modal(modalOption);
                    $(modalForm).trigger('openModal');
                });
            }
        });

        return $.CustomModal.modalHelp;
    }
);
