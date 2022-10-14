/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {
    $('body').on('click', '.js-grid-tool-overwrite', function(e){
        e.preventDefault();
        var parent = $(this).parents('.grid-item-conflict');
        var alert = parent.next();
        parent.find('> input[type=hidden]').val('overwrite');
        parent.addClass('grid-item--collapsed');
        parent.find('.toggle-content input[type=text]').prop('disabled', true);
        alert.find('.alert__title').text(EE.lang.portage_will_overwrite);
        alert.css('display', 'flex');
    })

    $('body').on('click', '.js-grid-tool-remove', function(e){
        e.preventDefault();
        var parent = $(this).parents('.grid-item-conflict');
        var alert = parent.next();
        parent.find('> input[type=hidden]').val('skip');
        parent.addClass('grid-item--collapsed');
        parent.find('.toggle-content input[type=text]').prop('disabled', true);
        parent.css('opacity', '50%');
        alert.find('.alert__title').text(EE.lang.portage_will_overwrite);
        alert.css('display', 'flex');
    })

    $('body').on('click', '.js-grid-tool-edit', function(e){
        e.preventDefault();
        var parent = $(this).parents('.grid-item-conflict');
        var alert = parent.next();
        parent.find('> input[type=hidden]').val('');
        parent.removeClass('grid-item--collapsed');
        parent.css('opacity', '100%');
        parent.find('.toggle-content input[type=text]').prop('disabled', false);
        alert.css('display', 'none');
        alert.find('.alert__title').empty();
    })
});

})(jQuery);
