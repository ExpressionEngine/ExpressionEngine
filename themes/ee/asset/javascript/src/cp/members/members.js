/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

    $('.modal form').on('submit', function(e) {

        // extra check by toggle button
        if ($(this).find('.ajax').length && $(this).find('button').hasClass('off')) {
            $(this).find('.ajax .member-delete-confirm-invalid').show();
            e.preventDefault();
        }

        if ($('input[name="heir_action"]:checked').val() == 'assign'
            && (($('input[type="radio"][name="heir"]').length
                && $('input[type="radio"][name="heir"]:checked').length == 0)
                    || $('input[type="hidden"][name="heir"]').val() == ''))
        {
            $('.modal .ajax .fieldset-invalid:not(.member-delete-confirm-invalid)').show();
            e.preventDefault();
        }

    });

    $('body').on('change', '.modal-confirm-delete .ajax .member-delete-confirm .toggle-btn, .modal-confirm-remove-member .ajax .member-delete-confirm .toggle-btn', function(){
        var toggle = $(this);
        if(toggle.hasClass('on')) {

            $('.ajax').find('.member-delete-confirm-invalid').hide();
        } else {
            $('.ajax').find('.member-delete-confirm-invalid').show();
        }
    });

});

})(jQuery);
