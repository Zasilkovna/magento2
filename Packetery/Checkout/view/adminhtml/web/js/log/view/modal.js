define('Packetery_Checkout/js/view/modal', [
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return {
        openModal: function (url) {
            var modalHtml = '<div id="log-modal" style="display:none;"><h1>Details</h1></div>';
            $('body').append(modalHtml);
            var modalOptions = {
                type: 'slide',
                title: 'Details',
                buttons: []
            };
            modal(modalOptions, $('#log-modal'));
            $('#log-modal').modal('openModal');
        }
    };
});
