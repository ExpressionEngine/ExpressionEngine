/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

new MutableSelectField('field_groups', EE.channelManager.fieldGroup)

var options = {
	onFormLoad: function(modal) {
		FieldManager.fireEvent('fieldModalDisplay', modal)

		EE.cp.fieldToggleDisable(modal)

		$('input[name=field_label]', modal).bind("keyup keydown", function() {
			$(this).ee_url_title('input[name=field_name]', true);
		});
	}
}

new MutableSelectField('custom_fields', Object.assign(EE.channelManager.fields, options))

new MutableSelectField('cat_group', EE.channelManager.catGroup)

var options = {
	onFormLoad: function(modal) {
		var $status_tag = $('.status-tag', modal);

		// Change the status example's name when you change the name
		$('input[name="status"]', modal).on('keyup', function(event) {
			var status = $(this).val() ? $(this).val() : EE.status.default_name;
			$status_tag.text(status);
		});

		$('input.color-picker', modal).minicolors({
			changeDelay: 200,
			change: function (value, opacity) {
				// Change background and border colors
				$status_tag.css('background-color', value)
					.css('border-color', value);

				// Get foreground color
				$.post(
					EE.status.foreground_color_url,
					{highlight: value},
					function (data) {
						$status_tag.css('color', '#'+data);
					},
					'json'
				);
			}
		});
	}
}

new MutableSelectField('statuses', Object.assign(EE.channelManager.statuses, options))

})(jQuery);
