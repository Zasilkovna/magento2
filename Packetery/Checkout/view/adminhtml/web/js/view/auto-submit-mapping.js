define(['mage/template', 'jquery'], function (mageTemplate, $) {
    'use strict';

    return function (config, element) {
        var rowTmpl = mageTemplate('#' + config.tmplId);
        var $tbody = $('#' + config.addRowId);

        function buildDefaultValues() {
            var values = {_id: '_' + Date.now(), option_extra_attrs: {}};
            $.each(config.columns, function (i, name) { values[name] = ''; });
            return values;
        }

        function addRow(rowData) {
            $tbody.append(rowTmpl(rowData || buildDefaultValues()));
            $.each((rowData && rowData.column_values) || {}, function (id, val) { $('#' + id).val(val); });
        }

        $tbody.closest('table').on('click', '.action-delete', function () {
            $(this).closest('tr').remove();
        });

        $('#' + config.addBtnId).on('click', function () { addRow(); });
        $tbody.closest('form').on('submit', function () {
            var seen = {};
            $tbody.find('tr').each(function () {
                var method = $(this).find('[name*="[payment_method]"]').val();
                var status = $(this).find('[name*="[order_status]"]').val();
                var key = method + '|' + status;

                // remove same payment_method, order_status combination
                if (!method || !status || seen[key]) {
                    $(this).remove();
                } else {
                    seen[key] = true;
                }
            });
        });

        $.each(config.rows, function (i, row) { addRow(row); });
        if (config.disabled) {
            toggleValueElements({checked: true}, element.parentNode);
        }
    };
});
