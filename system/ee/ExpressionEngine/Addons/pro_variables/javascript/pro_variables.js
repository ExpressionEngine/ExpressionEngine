/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// Make sure PRO namespace is valid
if (typeof PRO == 'undefined') {
    var PRO = new Object;
}

(function ($) {

// --------------------------------------
// Language lines
// --------------------------------------

    var lang = function (str) {
        return (typeof EE.PRO.lang[str] == 'undefined') ? str : EE.PRO.lang[str];
    }

// --------------------------------------
// Drag and drop lists object
// --------------------------------------

    PRO.DragLists = function () {

        var $els = $('.pro-drag-lists');

        if ( ! $els.length) {
            return;
        }

        // Loop through each drag list and add pixie dust
        $els.each(function () {

            var $el = $(this),
            name = $el.data('name'),
            $on = $el.find('.pro-on'),
            $off = $el.find('.pro-off');

            // Quick function to see if list item is On or not
            var isOn = function (li) {
                return $(li).parent().hasClass('pro-on');
            };

        // Define callback function
            var switched = function (event, obj) {
                $(obj.item).find('input').attr('name', (isOn(obj.item) ? name : ''));
            };

        // Initiate sortables
            $on.sortable({connectWith: $off, receive: switched});
            $off.sortable({connectWith: $on, receive: switched});

        // Add doubleclick event to lis in element
            $el.find('li').on('dblclick', function (event) {
                $(this).appendTo((isOn(this) ? $off : $on));
                switched(event, {item: this});
            });

        });

    };

    $(PRO.DragLists);

// --------------------------------------
// Table var type
// --------------------------------------

    PRO.ProTable = function () {

        var $els = $('[data-type="pro_table"]');

        if ( ! $els.length) {
            return;
        }

        $els.each(function () {

            var $el = $(this),
            $table = $el.find('table'),
            $tbody = $table.find('tbody'),
            $add   = $el.find('.toolbar .add a'),
            cols   = $table.find('thead th').length,
            rows   = $table.find('tbody tr').length,
            name   = $table.data('input-name');

            // Define addRow function
            $add.click(function (event) {

                // don't go anywhere
                event.preventDefault();

                // Create new row and append it to the table
                var $tr = $('<tr/>');
                $tbody.append($tr);

                // Loop thru cols and add <td><input /></td> for each one
                for (var i = 0; i < cols; i++) {
                    var $td = $('<td/>'),
                    $input = $('<input/>');

                    $input.attr({
                        'name': name + '[' + rows + '][' + i + ']',
                        'type': 'text'
                    });

                    $tr.append($td.append($input));
                }

                // Focus on the first one
                $tr.find('input').first().focus();

                // Increase row count
                rows++;
            });
        });
    };

    $(PRO.ProTable);

// --------------------------------------
// File Upload
// --------------------------------------

    PRO.NewFile = function () {

        var $els = $('.pro-new-file');

        if ( ! $els.length) {
            return;
        }

        $els.each(function () {

            // Determine vars
            var $el      = $(this),
            $toolbar = $el.find('.toolbar'),
            $toggle  = $el.find('a'),
            name     = $el.data('name');

            // Create file input field
            var $upload = $('<input/>').attr({
                'type': 'file',
                'name': name
            }).change(function () {
                $(this).val() ? $el.addClass('has-file') : $el.removeClass('has-file');
            }).insertAfter($toolbar);

            $toggle.on('click', function (event) {
                event.preventDefault();
                $upload.trigger('click');
            });

        });

    };

    $(PRO.NewFile);

// --------------------------------------
// Reorder folder list (var groups)
// --------------------------------------

    PRO.FolderList = function () {

        var $list = $('.folder-list').first();

        if ( ! $list.length) {
            return;
        }

        // Because EE changed fonts from 3.2 to 3.3
        $list.addClass(EE.PRO.iconFont);

        // Because EE doesn't do this itself (yet)
        $list.find('.remove a.m-link, a.m-link.remove').on('click', function (event) {
            console.log('here');
            event.preventDefault();

            var $remove = $(this),
            $modal  = $('.' + $remove.attr('rel')).first(),
            $items  = $modal.find('.checklist'),
            $input  = $modal.find('input[name="id"]');

            $items.empty();
            $items.append($('<li/>').addClass('last').html($remove.data('confirm')));
            $input.val($remove.data('id'));
        });

        // Adds ID to each list item
        $list.find('> li').each(function () {
            var $li = $(this),
            $a = $li.find('a').first(),
            href = $a.attr('href'),
            id = href.replace(/.*group\/(\d+).*/, '$1');

            if (id == 0) {
                return;
            }

            $li.addClass('pro-var-group');
            var $toolbar = $li.find('ul.toolbar');
            var $handle = $('<li class="reorder"><a href=""></a></li>');
            $toolbar.prepend($handle);
        });

        $list.sortable({
            items: '.pro-var-group',
            handle: '.reorder',
            axis: 'y',
            update: function () {
                var order = [];

                $list.find('[data-id]').each(function () {
                    order.push($(this).data('id'));
                });

                $.ajax({
                    url: EE.PRO.save_group_order_url,
                    type: 'POST',
                    data: {
                        CSRF_TOKEN: EE.CSRF_TOKEN,
                        groups: order.join('|')
                    }
                });
            }
        });
    };

    $(PRO.FolderList);

// --------------------------------------
// Edit links for Managers
// --------------------------------------

    PRO.EditLinks = function () {

        var $els = $('[data-name]');

        if ( ! $els.length) {
            return;
        }

        // Show edit link and var name for managers
        $els.each(function () {
            var $el   = $(this),
            $edit = $('<a/>'),
            $code = $('<code/>'),
            id    = $el.data('id'),
            name  = $el.data('name');

            var href = EE.PRO.edit_var_url;
            href = href.replace('%d', id);
            href += '&from=' + EE.PRO.group_id;

            // Create edit link
            $edit.addClass('ico settings fas fa-cog').attr('href', href);

            // Add text to code bit
            $code.text('{' + name + '}');

            // Where to put it?
            $el.find('.field-instruct label')
            .prepend($edit)
            .append($code);

        });
    };

    $(PRO.EditLinks);

// --------------------------------------
// Show skipped errors based on attrs
// --------------------------------------

    PRO.ShowSkipped = function () {

        var $els = $('.col-group[data-error]');

        if ( ! $els.length) {
            return;
        }

        // Show edit link and var name for managers
        $els.each(function () {
            var $el     = $(this),
            $target = $el.find('.setting-field'),
            error   = $el.data('error');

            $el.addClass('invalid');
            $target.append($('<em/>').html(error));
        });
    };

    $(PRO.ShowSkipped);

// --------------------------------------
// Edit Group
// --------------------------------------

    PRO.EditGroup = function () {

        // Edit group - sort variables in group
        $('.pro-vars-in-group').sortable({axis:'y'});

        // New Group trigger
        var $radio = $('input[name="save_as_new_group"]');

        if ( ! $radio.length) {
            return;
        }

        // Grab elements we need to show/hide
        var $els = $('[data-section-group="new_group_options"]');

        // Hide the elements
        $els.hide();
        $els.last().addClass('last');

        // Show/hide based on yes/no radio
        $radio.on('change', function () {
            var method = $radio.filter(':checked').val() == 'y' ? 'show' : 'hide';
            $els[method]();
        });

        $('a[data-toggle-for="save_as_new_group"]').on('click', function (event) {
            var method = $radio.val() != 'y' ? 'show' : 'hide';
            $els[method]();
        });
    };

    $(PRO.EditGroup);

// --------------------------------------
// Manage List: on/off icons
// --------------------------------------

    PRO.ManageList = function () {

        $('a.onoff').on('click', function (event) {

            // Cancel the click
            event.preventDefault();

            // Rember self
            var $self = $(this);

            // Post it
            $.post(
                this.href,
                {CSRF_TOKEN: EE.CSRF_TOKEN},
                function () {
                    $self.toggleClass('on'); }
            );

        });

    };

    $(PRO.ManageList);

// --------------------------------------

})(jQuery);
