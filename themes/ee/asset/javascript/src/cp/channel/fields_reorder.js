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

        console.log('ready');
    
        $('.js-list-group-wrap').eeTableReorder({
            sortableContainer: '.list-group',
            handle: '.list-item__handle',
            cancel: '.no-results',
            item: '.list-item',
            afterSort: function(row) {
                $.ajax({
                    url: EE.fields.reorder_url,
                    data: $('.js-list-group-wrap').parents('form').serialize(),
                    type: 'POST',
                    dataType: 'json',
                    error: function(xhr, text, error) {
                        // Let the user know something went wrong
                        if ($('body > .banner').length == 0) {
                            $('body').prepend(EE.alert.reorder_ajax_fail);
                        }
                    }
                });
            }
        });
    
    });
    
    })(jQuery);
    