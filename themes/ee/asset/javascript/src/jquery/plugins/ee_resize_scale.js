/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * This jQuery plugin handles scaling resize fields when resizing images
 *
 * Example usage:
 *	$('form').resize_scale({
 *		"resize_width": 	"#resize_width",
 *		"resize_height": 	"#resize_height",
 *		"submit_resize": 	"#submit_resize",
 *		"cancel_resize": 	"#cancel_resize",
 *		"default_height": 	$image_height,
 *		"default_width": 	$image_width
 *  });
 */

(function($) {
	var default_options = {
		// Selectors for inputs
		"resize_width": 	"#resize_width",
		"resize_height": 	"#resize_height",
		"submit_resize": 	"",
		"cancel_resize": 	"",

		"oversized_class": 	"oversized",
		"default_height": 	0,
		"default_width": 	0,
		"resize_confirm": 	"",

		// Callbacks
		"callback_resize": 	"",
		"callback_submit": 	"",
		"callback_cancel": 	""
	};

	$.fn.resize_scale = function(passed_options) {
		return this.each(function() {
			var options = $.extend({}, default_options, passed_options),
				$form = $(this),
				$resize_width = $(options.resize_width, $form),
				$resize_height = $(options.resize_height, $form),
				$submit_button = $(options.submit_resize, $form),
				$cancel_button = $(options.cancel_resize, $form);

			// Ensure default height and width are numbers
			options.default_height = parseInt(options.default_height, 10);
			options.default_width = parseInt(options.default_width, 10);

			$resize_width.add($resize_height).keyup(function(event) {
				// Enable cancel button
				$cancel_button.show();

				// Need to maintain proportions and resize image
				// In order to do this, I need to figure out ratio and adhere to it
				var $element = $(this),
					id = $element.attr('id'),
					$other_element = (id === "resize_height") ? $resize_width : $resize_height,
					image_ratio;

				// Determine ratio
				if (id === "resize_width")
				{
					image_ratio = options.default_height / options.default_width;

				}
				else
				{
					image_ratio = options.default_width / options.default_height;
				}

				// Change other element's value
				$other_element.val(Math.round(image_ratio * $element.val()));

				if ($resize_width.val() > options.default_width || $resize_height.val() > options.default_height)
				{
					$resize_width.addClass(options.oversized_class);
					$resize_height.addClass(options.oversized_class);
				}
				else
				{
					$resize_height.removeClass(options.oversized_class);
					$resize_width.removeClass(options.oversized_class);
				}

				if (typeof options.callback_resize === 'function') {
					options.callback_resize.call(
						this,
						{
							"width": $resize_width.val(),
							"height": $resize_height.val()
						}
					);
				};
			});

			$submit_button.off('click', '**').on('click', function(event) {
				if ($('.'+options.oversized_class).length) {
					var confirmation = confirm(options.resize_confirm);

					if (confirmation == false) {
						event.preventDefault();
					} else if (typeof options.callback_submit === 'function') {
						options.callback_submit.call(this);
					} else {
						$form.trigger('submit');
					}
				}
			});

			if ($cancel_button.length) {
				$cancel_button.click(function(event) {
					event.preventDefault();

					$resize_width.val(options.default_width).removeClass(options.oversized_class);
					$resize_height.val(options.default_height).removeClass(options.oversized_class);

					if (typeof options.callback_cancel === 'function') {
						options.callback_cancel.call(
							this,
							{
								"width": $resize_width.val(),
								"height": $resize_height.val()
							}
						);
					};

					$cancel_button.hide();
				});
			};
		});
	};
})(jQuery);
