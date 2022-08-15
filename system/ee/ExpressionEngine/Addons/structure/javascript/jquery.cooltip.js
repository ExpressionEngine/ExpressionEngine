/*!
 * jQuery Cooltip plugin
 * Version 1.0  (October 9, 2011)
 * @requires jQuery v1.4+
 * @author Karl Swedberg
 *
 * License: MIT
 * http://www.opensource.org/licenses/mit-license.php
 *
 * @method :
 * .cooltip(target, options)
 *
 * @arguments :
 *
 **  @target (selector string) mandatory
 **
 **  @options (object) optional
 offsetX: -15,
 offsetY: 10,
 contents: '',
 hiddenStyle: {
   left: '-1000em'
 },
        show: 'click',
        hide: 'click',
 **     className: 'cooltip', // string. class name of the tooltip
 **     xOffset: 10, // integer. pixels to the right of the mouse that the left edge tooltip will be displayed
 **     yOffset: 20, // integer.  pixels above or below the mouse that the top of the tooltip will be displayed
 **     contents: html/fn, // html or function from which the tooltip contents will be pulled
        onPopulate: $.noop
        clickOutsideHide: true,

 *
 */

(function ($) {
    $.fn.cooltip = function (selector, options) {
        if (!this.length) {
            return this;
        }
        var containers = this,
        opts = $.extend({}, $.fn.cooltip.defaults, options),
        tgt,
        tip = {
            link: function (e) {
                var t = $(e.target).is(selector) && e.target || $(e.target).closest(selector)[0] || false;
                return t;
            }
        };

        if ( !$('body').children('div.' + opts.className).length ) {
            var $cooltip = $('<div></div>', {
                'class': opts.className
            })
            .css({position: 'absolute'})
            .appendTo('body');
        }

        containers.unbind('.cooltip');
        containers.each(function () {
            var contents,
            $container = $(this),
            tipContents = '';

            var o = $.meta ? $.extend({}, opts, $container.data()) : opts;

            $container.bind('populate.cooltip', function (event) {
                tgt = tip.link(event);

                if (tgt) {
                    $container.data('link', tgt);
                    contents = o.contents;

                    if ( $.isFunction(o.contents) ) {
                          contents = o.contents.call(tgt) ;
                    }
                    o.onPopulate.call(tgt, $cooltip);
                    if (contents) {
                          $cooltip.html(contents);
                    }
                }
            });

            $container.bind('position.cooltip', function (event) {
                tgt = tip.link(event);
                if ( !tgt ) {
                    return;
                }

                var override,
                offset = $(tgt).offset();

                offset.left += o.offsetX || 0;
                offset.top += o.offsetY || 0;

                if ( $.isFunction(o.adjustPosition) ) {
                    override = o.adjustPosition.call(tgt, $cooltip, offset) || {};
                    $.extend(offset, override);
                }

                $cooltip.css(offset);
            });

            $(document).bind('hide.cooltip', function (event) {
                $container.data('link', null);
                $cooltip.css(o.hiddenStyle);
            });
        }); // end .each


        if (opts.show == opts.hide) {
        // toggle
            containers.each(function (index) {
                var $container = $(this);
                $container.delegate(selector, opts.show + '.cooltip', function (event) {
                    event.preventDefault();
                    var lastEl = $container.data('link');
                    if ( $cooltip.is(':visible') && $(lastEl).is(this) ) {
                        $(this).trigger('hide.cooltip');
                    } else {
                        $(this).trigger('populate.cooltip');
                        $(this).trigger('position.cooltip');
                    }
                });
            });
        } else {
      // show
            containers.delegate(selector, opts.show + '.cooltip', function (event) {
                event.preventDefault();

                $(this).trigger('populate.cooltip');
                $(this).trigger('position.cooltip');
            });

      // bind hide event
            containers.delegate(selector, opts.hide + '.cooltip', function (event) {
                event.preventDefault();
                var link = $cooltip.data('link');
                if ( opts.show != opts.hide || link && !$(link).is(this) ) {
                    $cooltip.data('link', null);
                    $(this).trigger('hide.cooltip');
                }
            });
        }

  // trigger hide on click outside
        if (opts.clickOutsideHide) {
            $(document.body).unbind('click.cooltip').bind('click.cooltip', function (event) {
                var tgt = event.target,
                  $tgt = $(tgt),
                  visible = parseInt($cooltip.css('left'), 10) > 0 && parseInt($cooltip.css('top'), 10) > 0;

                if ( visible && !$tgt.closest(selector).length && !$tgt.closest('.' + opts.className).length ) {
                    $(document).trigger('hide.cooltip');
                }
            });
        }
  // trigger hide on Esc key
        containers.bind('keyup.cooltip', function (event) {
            if (event.which == 27) {
                $(this).trigger('hide.cooltip');
            }
        });
        return this;
    };

// default options
    $.fn.cooltip.defaults = {
        show: 'click',
        hide: 'click',
        clickOutsideHide: true,
        className: 'popovers',
        offsetX: -15,
        offsetY: 10,
        contents: '',
        hiddenStyle: {
            left: '-1000em'
        },
        onPopulate: $.noop
    };

})(jQuery);
