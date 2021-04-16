;(function(global, $){
    //es5 strict mode
    "use strict";

    var Editor = global.Editor = global.Editor || {};

    $('.redactor-toolbar-config ul').sortable({
        axis: 'x'
    });

    // ----------------------------------------------------------------------

    global.EditorSettingsInit = function (){
        $('.editor-settings').each(function(){
            var parentHolder = $(this);
            if (parentHolder.hasClass('editor-initialized')) return;
            var holder = parentHolder.find('.settings-holder');

            // JSON Data
            var data = $.parseJSON(parentHolder.find('script').html());
            parentHolder.data('settings', data);

            // Advanced Settings
            parentHolder.find('select').change(addSetting);
            parentHolder.on('click', '.advsetting .remove', deleteSetting);

            if (data.options_current) {
                for (var optionKey in data.options_current) {
                    if (!data.options[optionKey]) continue;

                    var option = data.options[optionKey];
                    var current = data.options_current[optionKey];
                    var formName = getFormName(parentHolder) + '[' + optionKey + ']';
                    appendOption(optionKey, option, formName, holder, current);
                }
            }

            parentHolder.addClass('editor-initialized');
        });
    };

    // Initialize!
    global.EditorSettingsInit();

    // ----------------------------------------------------------------------

    function addSetting(evt) {
        evt.preventDefault();

        var target = $(evt.target);
        var parentHolder = target.closest('.editor-settings');
        var holder = parentHolder.find('.settings-holder');
        var advSettings = parentHolder.data('settings');
        var optionKey = target.val();
        target.val('');

        // Get the option
        if (!advSettings.options[optionKey]) return;
        if (holder.find('.setting-' + optionKey).length > 0) return;

        var formName = getFormName(parentHolder) + '[' + optionKey + ']';
        var option = advSettings.options[optionKey];

        appendOption(optionKey, option, formName, holder, false);
    }

    // ----------------------------------------------------------------------

    function appendOption(optionKey, option, formName, holder, current) {
        var field = '';

        if (current !== false) {
            option.value = current;
        }

        if (option.type == 'text' || option.type == 'text-array') {
            field += '<input type="text" name="' + formName + '" class="text" value="' + option.value + '">';
        }

        if (option.type == 'number' || option.type == 'number-bool') {
            field += '<input type="number" name="' + formName + '" class="number" value="' + option.value + '">';
        }

        if (option.type == 'bool') {
            field += '<label class="choice mr ' + ((option.value == 'yes') ? 'chosen' : '') + ' yes">';
            field += '<input type="radio" name="' + formName + '" value="yes" ' + ((option.value == 'yes') ? 'checked' : '') + '> Yes';
            field += '</label>';

            field += '<label class="choice ' + ((option.value == 'no') ? 'chosen' : '') + ' no">';
            field += '<input type="radio" name="' + formName + '" value="no" ' + ((option.value == 'no') ? 'checked' : '') + '> No';
            field += '</label>';
        }

        if (option.type == 'radio') {
            for (var i in option.options) {
                field += '<label class="choice mr ' + ((option.value == i) ? 'chosen' : '') + ' ">';
                field += '<input type="radio" name="' + formName + '" value="'+ i +'" ' + ((option.value == i) ? 'checked' : '') + '> ' + option.options[i];
                field += '</label>';
            }
        }

        if (option.type == 'select') {
            field += '<select name="' + formName + '">';

            for (var i in option.options) {
                field += '<option value="'+ i +'" ' + ((option.value == i) ? 'selected' : '') + '>' + option.options[i] + '</option>';
            }

            field += '</select>';
        }

        var html = '';
        html += '<fieldset class="col-group advsetting setting-' + optionKey + '">';
        html +=     '<div class="setting-txt advsetting-txt col w-8">';
        html +=         '<h3>' + optionKey + '</h3>';
        html +=         '<em>' + option.desc + '</em>';
        html +=     '</div>';
        html +=     '<div class="setting-field advsetting-field col w-7">';
        html +=         field;
        if (option.exp) html +=         '<small>' + option.exp + '</small>';
        html +=     '</div>';
        html +=     '<div class="advsetting-toolbar col w-1 last">';
        html +=         '<ul class="toolbar"><li class="remove"><a href="#" title="remove row"></a></li></ul>';
        html +=     '</div>';
        html += '</fieldset>';

        holder.append(html);
    }

    // ----------------------------------------------------------------------

    function getFormName(holder) {
        var formName = '';
        var dummy = holder.find('.dummy')
        var originalName = dummy.data('name');
        var currentName = dummy.attr('name');

        if (currentName.indexOf('['+originalName+']') > 0) {
            formName = currentName.split('['+originalName+']')[0];
        } else {
            return originalName;
        }

        return formName;
    }

    // ----------------------------------------------------------------------

    function deleteSetting(evt) {
        evt.preventDefault();

        $(evt.target).closest('.advsetting').fadeOut('slow', function(){
            $(this).remove();
        });
    }

    // ----------------------------------------------------------------------

}(window, jQuery));