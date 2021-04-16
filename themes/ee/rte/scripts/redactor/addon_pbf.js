;(function(global, $){
    //es5 strict mode
    "use strict";

    var Editor = global.Editor = global.Editor || {};

    Editor.matrixColConfigs = {};
    Editor.contentElementsConfig = {};
    Editor.gridConfig = {};

    if ('undefined' !== typeof window['Grid']) gridInit();

    // ----------------------------------------------------------------------

    function gridInit() {
        Grid.bind('editor', 'display', gridDisplay);
    }

    // ----------------------------------------------------------------------

    function gridDisplay(cell) {
        var config = Editor.gridConfig[cell.find('.redactor_editor').data('config_key')];
        cell.find('.redactor_editor').redactor(config);
    }

    // ----------------------------------------------------------------------



}(window, jQuery));