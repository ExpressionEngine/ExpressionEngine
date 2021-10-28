/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

    $('.modal form').on('submit', function(e) {
        if ($('input[name="heir_action"]:checked').val() == 'assign'
            && (($('input[type="radio"][name="heir"]').length
                && $('input[type="radio"][name="heir"]:checked').length == 0)
                    || $('input[type="hidden"][name="heir"]').val() == ''))
        {
            $('.modal .ajax .fieldset-invalid').show();
            e.preventDefault();
        }
    });

});

})(jQuery);
