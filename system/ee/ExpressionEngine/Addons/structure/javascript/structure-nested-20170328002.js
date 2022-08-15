
(function ($) {
  /*  nestedStructure widget 1.0
  * by Karl Swedberg (for Fusionary)
  *
  * extends the nestedSortable widget
  * so it can use the same data structure as the old inestedsortable
  * and still work with the PHP
  */

    if (!$ || !$.ui || !$.fn.nestedSortable) {
        return;
    }

    $.widget('fm.nestedStructure', $.ui.nestedSortable, {
        toNested: function ($container) {
            var containerId = $container.attr('id') || 'page-ui',
            userOpts = this.options,
            nestedArray = [],
            ret = {},
            settings = {
                items: 'li',
                idPrefix: 'page-'
            },
            opts = $.extend(true, {}, settings, userOpts);
            if (opts.listType == 'ol') {
                opts.listType = 'ul, ol';
            }

            var buildNest = function (item, i) {
                var retVal = {},
                id = item.id || i,
                thisChildren = opts.listType ?
                $(item).children(opts.listType).children(opts.items) :
                $(item).children(opts.items);

                if (id.indexOf(opts.idPrefix) === 0) {
                    id = id.slice(opts.idPrefix.split('').length);
                }
                retVal.id = id;

                if (thisChildren.length) {
                    retVal.children = [];
                    thisChildren.each(function (index) {
                        retVal.children[index] = buildNest(this, index);
                    });
                }
                return retVal;
            };

            $container.children(opts.items).each(function (index) {
                nestedArray[index] = buildNest(this, index);
            });
            ret[containerId] = nestedArray;
            return ret;
        }
    });
})(jQuery);

(function ($) {
    var reordering = 0;

    $(document).ready(function () {
        var nestedOpts,
        ajaxURL = $('#structure-ui').data('ajaxreorder'),
        XID = structure_settings.xid,
        $structureUi = $('#structure-ui');


      // Nested Structure drag 'n drop
        if (structure_settings.can_reorder === false ) {
            return;
        }

      // shadow div to follow along with ui-helper but at full width
        var $shadow = $('<div></div>', {
            id: 'structure-shadow',
            html: '<span></span>'
        }).appendTo('body');

        var $overlay = $('<div></div>', {
            id: 'structure-overlay',
            html: '<span></span>',
            style: 'display:none;'
        }).appendTo('body');

      // options that apply to all sortable lists
        nestedOpts = {
            handle: '.drag-handle',
            forcePlaceholderSize: false,
            tabSize: 20,
            tolerance: 'pointer',
            toleranceElement: 'div.item-inner',
            placeholder: 'placeholder',
            items: 'li',
            collapsedClass: 'state-collapsed',
            listType: 'ul',
            start: function (e, ui) {
                $shadow.css({
                    width: ( $structureUi.width() - 2 ) + 'px',
                    left: $structureUi.offset().left  + 'px'
                }).fadeIn(200);
                var hul = ui.helper.children('ul').not('.state-collapsed');
                hul.data('height', hul.height()).animate({height: 0}, 200);
                ui.placeholder.html('<div></div>');
            },
            change: function (e, ui) {
                if (ui.placeholder.offset().top >= $structureUi.offset().top ) {
                    $shadow.css({top: ui.placeholder.offset().top});
                    $shadow.find('span').css('marginLeft', (ui.offset.left) + 'px');
                }
            },
            beforeStop: function (e, ui) {
                var hul = ui.helper.children('ul').not('.state-collapsed');
                hul.animate({height: hul.data('height')}, 200, function () {
                    hul.css('height', '');
                });
            }
        };

      // loop through nav groups and make them sortable
        $structureUi.children('ul.page-ui').each(function () {
            var $pageUi = $(this);
            $pageUi.find('li').last().addClass('final-child');

          // easier to merge the stop() callback into the opts here
          // because we can use a ref to $pageUi instead of finding it each time
            var nOpts = $.extend({}, nestedOpts, {
                stop: function (e, ui) {
                  // Check if we're reordering already and if so, fail to prevent a race
                  // condition, otherwise, mark that we're reordering now.
                    if (reordering === 1) {
                        return false;
                    }

                    $overlay.show();

                  // Set our reordering flag and display a wait cursor
                    reordering = 1;

                    $shadow.fadeOut(200);

                  // combine all trees into one for proper ordering
                    var fulltree = $('<ul class="page-ui">').append($('ul.page-ui').find('> li').clone());
                    var reorder = $pageUi.nestedStructure('toNested', fulltree);

                  //var reorder = $pageUi.nestedStructure('toNested', $pageUi);
                    reorder.XID = XID;
                    reorder.timestamp = structure_updated;

                    console.log('Updated:', structure_updated);
                    $.post(ajaxURL, reorder, function (response) {
                      // Unset our reordering flag
                        reordering = 0;

                      // Hide the overlay
                        $overlay.hide();

                        console.log(response);

                        var result = JSON.parse(response);

                      // If our sort was successful, update the last sorted timestamp, otherwise, display the error.
                        if (result.success === true) {
                            structure_updated = result.timestamp;
                        } else if (result.success === false && result.errors) {
                            alert(result.errors);
                        } else {
                            alert("Reorder Failed:\n\n" + response);
                            console.log(response);
                        }

                        $pageUi.trigger('collapsibles');
                        ui.item.find('.page-expand-collapse').eq(0).click();
                    });

                    ui.item.removeClass('final-child');
                    $pageUi.find('li').last().addClass('final-child');
                }

            });

          // call the nested structure widget on this list
            $pageUi.nestedStructure(nOpts);
        });

    });

})(jQuery);

