define([
    'jquery',
    'Magento_Ui/js/grid/columns/actions',
    'Magento_Ui/js/modal/modal'
], function ($, Actions, modal) {
    'use strict';

    var DETAIL_PATH = 'packetery/log/detail';
    var MODAL_QUERY_FLAG = 'modal=1';
    var MODAL_PATH_FLAG = '/modal/1/';

    return Actions.extend({
        packeteryLogDetailModalWrapper: null,

        isHandlerRequired: function (actionIndex, rowIndex) {
            var action = this.getAction(rowIndex, actionIndex);

            if (this.isPacketeryLogDetailAction(action)) {
                return true;
            }

            return this._super(actionIndex, rowIndex);
        },

        applyAction: function (actionIndex, rowIndex) {
            var action = this.getAction(rowIndex, actionIndex);

            if (this.isPacketeryLogDetailAction(action)) {
                this.openLogDetailModal(action.href);

                return false;
            }

            return this._super(actionIndex, rowIndex);
        },

        isPacketeryLogDetailAction: function (action) {
            if (!action || !action.href) {
                return false;
            }

            return action.href.indexOf(DETAIL_PATH) !== -1 &&
                (action.href.indexOf(MODAL_QUERY_FLAG) !== -1 || action.href.indexOf(MODAL_PATH_FLAG) !== -1);
        },

        openLogDetailModal: function (href) {
            var wrapper = this.getPacketeryLogDetailModalWrapper();

            wrapper.modal('openModal');
            this.renderModalNotice($.mage.__('Loading...'));

            this.loadLogDetailHtml(href).done(function (responseHtml) {
                var detailHtml = this.extractLogDetailHtml(responseHtml);

                if (detailHtml === null) {
                    this.renderModalError($.mage.__('Unable to load detail.'));

                    return;
                }

                this.getPacketeryLogDetailModalContent().html(detailHtml);
            }.bind(this)).fail(function () {
                this.renderModalError($.mage.__('Unable to load detail.'));
            }.bind(this));
        },

        loadLogDetailHtml: function (href) {
            return $.ajax({
                url: href,
                method: 'GET',
                dataType: 'html'
            });
        },

        extractLogDetailHtml: function (responseHtml) {
            var parser = new DOMParser(),
                parsedHtml = parser.parseFromString(responseHtml, 'text/html'),
                detailContainer = parsedHtml.querySelector('.packetery-log-detail-page');

            if (!detailContainer) {
                return null;
            }

            return detailContainer.outerHTML;
        },

        renderModalNotice: function (message) {
            this.getPacketeryLogDetailModalContent().html(this.buildMessageHtml('notice', message));
        },

        renderModalError: function (message) {
            this.getPacketeryLogDetailModalContent().html(this.buildMessageHtml('error', message));
        },

        buildMessageHtml: function (type, message) {
            return '<div class="message message-' + type + '"><div>' + message + '</div></div>';
        },

        getPacketeryLogDetailModalWrapper: function () {
            if (this.packeteryLogDetailModalWrapper !== null) {
                return this.packeteryLogDetailModalWrapper;
            }

            this.packeteryLogDetailModalWrapper = $('<div id="packetery-log-detail-modal" />');
            modal(
                {
                    type: 'slide',
                    responsive: true,
                    innerScroll: true,
                    clickableOverlay: true,
                    title: '',
                    buttons: []
                },
                this.packeteryLogDetailModalWrapper
            );
            $('body').append(this.packeteryLogDetailModalWrapper);

            return this.packeteryLogDetailModalWrapper;
        },

        getPacketeryLogDetailModalContent: function () {
            var wrapper = this.getPacketeryLogDetailModalWrapper(),
                slide = wrapper.closest('.modal-slide'),
                content = slide.find('[data-role="content"]').first();

            if (content.length === 0) {
                content = $('.modal-slide._show [data-role="content"]').last();
            }

            return content;
        }
    });
});
