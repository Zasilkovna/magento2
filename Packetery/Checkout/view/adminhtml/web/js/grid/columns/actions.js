define([
    'jquery',
    'Magento_Ui/js/grid/columns/actions',
    'Magento_Ui/js/modal/modal'
], function ($, Actions, modal) {
    'use strict';

    var PRINT_LABEL_FORM_PATH = 'packetery/packet/printlabelform';
    var MODAL_QUERY_FLAG = 'modal=1';
    var MODAL_PATH_FLAG = '/modal/1/';

    return Actions.extend({
        packeteryPrintLabelModalWrapper: null,
        packeteryOriginalPrintLabelText: null,
        packeteryPrintInProgress: false,

        isHandlerRequired: function (actionIndex, rowIndex) {
            var action = this.getAction(rowIndex, actionIndex);

            if (this.isPacketeryPrintLabelModalAction(action)) {
                return true;
            }

            return this._super(actionIndex, rowIndex);
        },

        applyAction: function (actionIndex, rowIndex) {
            var action = this.getAction(rowIndex, actionIndex);

            if (this.isPacketeryPrintLabelModalAction(action)) {
                this.openPrintLabelModal(action.href);

                return false;
            }

            return this._super(actionIndex, rowIndex);
        },

        isPacketeryPrintLabelModalAction: function (action) {
            if (!action || !action.href) {
                return false;
            }

            return action.href.indexOf(PRINT_LABEL_FORM_PATH) !== -1 &&
                (action.href.indexOf(MODAL_QUERY_FLAG) !== -1 || action.href.indexOf(MODAL_PATH_FLAG) !== -1);
        },

        openPrintLabelModal: function (href) {
            var wrapper = this.getPacketeryPrintLabelModalWrapper();

            wrapper.modal('openModal');
            this.renderModalNotice($.mage.__('Loading...'));

            this.loadPrintLabelFormHtml(href).done(function (responseHtml) {
                var formHtml = this.extractPrintLabelFormHtml(responseHtml);

                if (formHtml === null) {
                    this.renderModalError($.mage.__('Unable to load print form.'));

                    return;
                }

                this.renderPrintLabelForm(formHtml);
            }.bind(this)).fail(function () {
                this.renderModalError($.mage.__('Unable to load print form.'));
            }.bind(this));
        },

        loadPrintLabelFormHtml: function (href) {
            return $.ajax({
                url: href,
                method: 'GET',
                dataType: 'html'
            });
        },

        extractPrintLabelFormHtml: function (responseHtml) {
            var parser = new DOMParser(),
                parsedHtml = parser.parseFromString(responseHtml, 'text/html'),
                formContainer = parsedHtml.querySelector('.packetery-print-label-page');

            if (!formContainer) {
                return null;
            }

            return formContainer.outerHTML;
        },

        renderPrintLabelForm: function (formHtml) {
            var modalContent = this.getPacketeryPrintLabelModalContent();

            modalContent.html(formHtml);
            modalContent.off('click.packeteryModalCancel').on('click.packeteryModalCancel', '.packetery-print-label-cancel', function (event) {
                event.preventDefault();
                this.closePacketeryPrintLabelModal();
            }.bind(this));
            modalContent.off('submit.packeteryModalSubmit').on('submit.packeteryModalSubmit', 'form', function (event) {
                event.preventDefault();
                this.submitPrintLabelForm(event.currentTarget);
            }.bind(this));
        },

        submitPrintLabelForm: function (formElement) {
            var form = $(formElement),
                action = form.attr('action'),
                query = form.serialize(),
                requestUrl = action + (action.indexOf('?') === -1 ? '?' : '&') + query,
                backUrl = form.find('.packetery-print-label-cancel').attr('href') || window.location.href;

            this.packeteryPrintInProgress = true;
            this.removePrintLabelFormError();
            this.setPrintLoadingState(true);
            this.setModalInteractionLock(true);
            // 'manual' keeps unexpected redirects (expired session) unfollowed,
            // errors arrive as JSON thanks to the AJAX header.
            window.fetch(requestUrl, {
                redirect: 'manual',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                var contentType = response.headers.get('Content-Type') || '';

                if (response.type === 'opaqueredirect') {
                    this.resetPrintRequestState();
                    this.closePacketeryPrintLabelModal();
                    window.location.href = backUrl;

                    return null;
                }

                if (contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function (data) {
                        if (data.ajaxExpired && data.ajaxRedirect) {
                            this.resetPrintRequestState();
                            this.closePacketeryPrintLabelModal();
                            window.location.href = data.ajaxRedirect;

                            return null;
                        }

                        this.resetPrintRequestState();
                        this.renderPrintLabelFormError(data.message || $.mage.__('The label could not be generated.'));

                        return null;
                    }.bind(this));
                }

                if (!response.ok || contentType.indexOf('application/pdf') === -1) {
                    this.resetPrintRequestState();
                    this.renderPrintLabelFormError($.mage.__('The label could not be generated.'));

                    return null;
                }

                return response.blob();
            }.bind(this)).then(function (responseBlob) {
                var blobUrl = null;

                if (responseBlob === null) {
                    return;
                }

                blobUrl = URL.createObjectURL(responseBlob);
                window.open(blobUrl, '_blank');
                this.resetPrintRequestState();
                this.closePacketeryPrintLabelModal();
            }.bind(this)).catch(function () {
                this.resetPrintRequestState();
                this.renderPrintLabelFormError($.mage.__('The label could not be generated.'));
            }.bind(this));
        },

        renderPrintLabelFormError: function (message) {
            var page = this.getPacketeryPrintLabelModalContent().find('.packetery-print-label-page'),
                messageElement = null;

            if (page.length === 0) {
                this.renderModalError(message);

                return;
            }

            messageElement = $(this.buildMessageHtml('error', ''));
            messageElement.children('div').text(message);
            this.removePrintLabelFormError();
            page.find('form').before(messageElement);
        },

        removePrintLabelFormError: function () {
            this.getPacketeryPrintLabelModalContent().find('.packetery-print-label-page > .message').remove();
        },

        resetPrintRequestState: function () {
            this.setPrintLoadingState(false);
            this.packeteryPrintInProgress = false;
            this.setModalInteractionLock(false);
        },

        setPrintLoadingState: function (isLoading) {
            var modalContent = this.getPacketeryPrintLabelModalContent(),
                printButton = modalContent.find('button.action-primary'),
                cancelLink = modalContent.find('.packetery-print-label-cancel');

            if (isLoading) {
                if (this.packeteryOriginalPrintLabelText === null && printButton.length > 0) {
                    this.packeteryOriginalPrintLabelText = printButton.text();
                }

                printButton.prop('disabled', true).addClass('disabled');
                printButton.find('span').text($.mage.__('Generating...'));
                cancelLink.addClass('disabled');

                return;
            }

            printButton.prop('disabled', false).removeClass('disabled');
            if (this.packeteryOriginalPrintLabelText !== null) {
                printButton.find('span').text(this.packeteryOriginalPrintLabelText);
            } else {
                printButton.find('span').text($.mage.__('Print'));
            }
            cancelLink.removeClass('disabled');
        },

        setModalInteractionLock: function (isLocked) {
            var wrapper = this.getPacketeryPrintLabelModalWrapper(),
                slide = wrapper.closest('.modal-slide'),
                closeButton = slide.find('[data-role="closeBtn"]'),
                overlay = $('.modals-overlay').last();

            if (isLocked) {
                closeButton.prop('disabled', true).addClass('disabled');
                overlay.css('pointer-events', 'none');
                return;
            }

            closeButton.prop('disabled', false).removeClass('disabled');
            overlay.css('pointer-events', '');
        },

        renderModalNotice: function (message) {
            this.getPacketeryPrintLabelModalContent().html(this.buildMessageHtml('notice', message));
        },

        renderModalError: function (message) {
            this.getPacketeryPrintLabelModalContent().html(this.buildMessageHtml('error', message));
        },

        buildMessageHtml: function (type, message) {
            return '<div class="message message-' + type + '"><div>' + message + '</div></div>';
        },

        getPacketeryPrintLabelModalWrapper: function () {
            if (this.packeteryPrintLabelModalWrapper !== null) {
                return this.packeteryPrintLabelModalWrapper;
            }

            this.packeteryPrintLabelModalWrapper = $('<div id="packetery-print-label-modal" />');
            modal(
                {
                    type: 'slide',
                    responsive: true,
                    innerScroll: true,
                    clickableOverlay: true,
                    title: '',
                    buttons: []
                },
                this.packeteryPrintLabelModalWrapper
            );
            $('body').append(this.packeteryPrintLabelModalWrapper);

            return this.packeteryPrintLabelModalWrapper;
        },

        getPacketeryPrintLabelModalContent: function () {
            var wrapper = this.getPacketeryPrintLabelModalWrapper(),
                slide = wrapper.closest('.modal-slide'),
                content = slide.find('[data-role="content"]').first();

            if (content.length === 0) {
                content = $('.modal-slide._show [data-role="content"]').last();
            }

            return content;
        },

        closePacketeryPrintLabelModal: function () {
            if (this.packeteryPrintInProgress) {
                return;
            }

            if (this.packeteryPrintLabelModalWrapper !== null) {
                this.packeteryPrintLabelModalWrapper.modal('closeModal');
            }
        }
    });
});
