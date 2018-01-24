/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

(function($) {

"use strict";

$(document).ready(function() {

    $('.modal form').on('submit', function(e) {
        if ($('input[name="heir_action"]:checked').val() == 'assign'
            && $('input[name="heir"]:checked').length == 0)
        {
            $('.modal .ajax .ee-form-error-message').show();
            e.preventDefault();
        }
    });

});

})(jQuery);
