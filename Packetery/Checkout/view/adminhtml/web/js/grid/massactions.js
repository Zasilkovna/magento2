define([
    'jquery',
    'underscore',
    'Magento_Ui/js/grid/massactions'
], function ($, _, Massactions) {
    'use strict';

    var NEW_TAB_ACTIONS = [
        'print_packeta_labels',
        'print_carrier_labels'
    ];

    return Massactions.extend({
        defaultCallback: function (action, data) {
            var itemsType = data.excludeMode ? 'excluded' : 'selected',
                selections = {};

            if (_.contains(NEW_TAB_ACTIONS, action.type) === false) {
                return this._super(action, data);
            }

            selections[itemsType] = data[itemsType];
            if (!selections[itemsType].length) {
                selections[itemsType] = false;
            }
            _.extend(selections, data.params || {});

            this.submitForm(action.url, this.extendWithFormKey(selections), '_self');
        },

        extendWithFormKey: function (data) {
            var payload = $.extend(true, {}, data),
                formKey = window.FORM_KEY ? String(window.FORM_KEY) : '';

            if (formKey !== '') {
                payload.form_key = formKey;
            }

            return payload;
        },

        appendFormValue: function (form, name, value) {
            if (_.isArray(value)) {
                _.each(value, function (arrayValue) {
                    this.appendFormValue(form, name + '[]', arrayValue);
                }, this);

                return;
            }

            if (_.isObject(value) && value !== null) {
                _.each(value, function (objectValue, objectKey) {
                    this.appendFormValue(form, name + '[' + objectKey + ']', objectValue);
                }, this);

                return;
            }

            form.append($('<input/>', {
                type: 'hidden',
                name: name,
                value: value
            }));
        },

        submitForm: function (url, data, target) {
            var form = $('<form/>', {
                method: 'post',
                action: url,
                target: target,
                style: 'display:none'
            });

            _.each(data, function (value, name) {
                this.appendFormValue(form, name, value);
            }, this);

            $('body').append(form);
            form.trigger('submit');
            form.remove();
        }
    });
});
