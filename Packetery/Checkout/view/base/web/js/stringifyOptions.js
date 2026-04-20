/**
 * Shared utility functions for transform widget options to human-readable string
 * @copyright Packeta s.r.o.
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */
define([], function () {
    'use strict';

    var stringifyOptions = function (widgetOptions, depth) {
        if (depth === undefined) {
            depth = 0;
        }
        if (depth > 8) {
            return '...';
        }

        if (widgetOptions == null || typeof widgetOptions !== 'object') {
            return String(widgetOptions);
        }

        if (Array.isArray(widgetOptions)) {
            return '[' + widgetOptions.map(function (item) {
                return stringifyOptions(item, depth + 1);
            }).join(', ') + ']';
        }

        var widgetOptionsArray = [];
        for (var property in widgetOptions) {
            if (Object.prototype.hasOwnProperty.call(widgetOptions, property)) {
                widgetOptionsArray.push(property + ': ' + stringifyOptions(widgetOptions[property], depth + 1));
            }
        }

        return widgetOptionsArray.join(', ');
    };

    return stringifyOptions;
});