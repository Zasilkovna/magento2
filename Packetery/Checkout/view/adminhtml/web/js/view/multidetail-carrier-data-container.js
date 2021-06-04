define([
    'underscore',
    'uiComponent',
], function(
    _,
    Component,
) {
    'use strict';

    var changeVisibility = function(dataContainer, visible) {
        var elems = dataContainer.elems();
        for(var key in elems) {
            if(elems.hasOwnProperty(key)) {
                elems[key].visible(visible);
            }
        }
    };

    var mixin = {
        hide: function() {
            changeVisibility(this, false);
        },
        show: function() {
            changeVisibility(this, true);
        }
    };

    return Component.extend(mixin);
});
