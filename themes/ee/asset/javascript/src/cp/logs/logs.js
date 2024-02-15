/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function(){

    // Table checkbox selection and bulk action display
    // -------------------------------------------------------------------

    // Highlight table rows when checked
    $('body').on('click', 'table tr', function(event) {
        if ($(this).find('input[type=checkbox]').length==1) {
            if (event.target.nodeName != 'A') {
                $(this).children('td:first-child').children('input[type=checkbox]').click();
            }
        }
    });

    // Prevent clicks on checkboxes from bubbling to the table row
    $('body').on('click', 'table tr td:first-child input[type=checkbox], table tr td.app-listing__cell', function(e) {
        e.stopPropagation();
    });

    // Toggle the bulk actions
    $('body').on('change', 'table tr td:first-child input[type=checkbox], table tr th:first-child input[type=checkbox]', function() {
        if ($(this).parents('form').find('.bulk-action-bar').length > 0 || $(this).parents('form').find('.tbl-bulk-act').length > 0) {
            $(this).parents('tr').toggleClass('selected', $(this).is(':checked'));
            if ($(this).parents('table').find('input:checked').length == 0) {
                $(this).parents('.tbl-wrap, .table-responsive').siblings('.bulk-action-bar, .tbl-bulk-act').addClass('hidden');
            } else {
                $(this).parents('.tbl-wrap, .table-responsive').siblings('.bulk-action-bar, .tbl-bulk-act').removeClass('hidden');
            }
        }
    });


    $('body').on('click', '.js-delete-all-logs', function (e) {
        e.preventDefault();
        var modalIs = '.' + $(this).attr('rel');
        var modal = $(modalIs+', [rel='+$(this).attr('rel')+']')

        $(modalIs + " .checklist").html(''); // Reset it
        $(modalIs + " .checklist").append('<li><b>' + $(this).data('confirm') + '</b></li>');
        $(modalIs + " input[name='selection']").val($(this).data('selection'));

        modal.trigger('modal:open')
    })

}); // close (document).ready
