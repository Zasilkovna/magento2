define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, _, Select) {
    'use strict';

    return Select.extend({
        /**
         * Core admin select renders options through the optgroup binding, which ignores the disabled flag.
         * So we disable soft-deleted box options in the DOM (not re-selectable) and re-apply on every re-render.
         */
        initialize: function () {
            this._super();
            $.async('#' + this.uid, function (select) {
                this.disableDeletedOptions(select);
                new MutationObserver(
                    this.disableDeletedOptions.bind(this, select)
                )
                    .observe(select, {
                        childList: true
                    });
            }.bind(this));

            return this;
        },

        disableDeletedOptions: function (select) {
            if (!select || !select.options) {
                return;
            }

            var deleted = {};

            this.options().forEach(function (option) {
                if (option.disabled) {
                    deleted[String(option.value)] = true;
                }
            });

            _.each(select.options, function (node) {
                node.disabled = deleted[String(node.value)] === true;
            });
        }
    });
});
