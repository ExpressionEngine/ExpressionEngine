/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('.modal-remove-field .button').on('click', function(e) {
			e.preventDefault();
			$('.form-standard form').off('submit');

			// Cannot use .submit() because we have inputs named "submit", see
			// https://api.jquery.com/submit/
			$('button[type=submit][value=save]').click();
		});

		$('.form-standard form').on('submit', function(e) {
			var existing_fields = EE.fields.fluid_field.fields;
			var existing_groups = EE.fields.fluid_field.groups;
			var field;
			var showModal = false;

			for (var i = 0, len = existing_fields.length; i < len; i++) {
				field = $('[name="field_channel_fields[]"][value="' + existing_fields[i] + '"]');
				if (field.size() == 0 || // Hidden input from React
					(field.attr('type') == 'checkbox' && field.prop('checked') == false)) { // Real checkbox
					showModal = true;
					break;
				}
			}

			for (var i = 0, len = existing_groups.length; i < len; i++) {
				field = $('[name="field_channel_field_groups[]"][value="' + existing_groups[i] + '"]');
				if (field.size() == 0 || // Hidden input from React
					(field.attr('type') == 'checkbox' && field.prop('checked') == false)) { // Real checkbox
					showModal = true;
					break;
				}
			}

			if (showModal) {
				e.preventDefault();
				$('.modal-remove-field .button').attr('disabled', false);
				$('.modal-remove-field').trigger('modal:open');
			}
		});
	});
})(jQuery);