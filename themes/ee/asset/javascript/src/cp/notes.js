/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {
	$(document).ready(function() {
		$("input[name='field_type']").change(function() {
			if ($(this).val() == "notes") {
				$('#fieldset-field_instructions').hide();
				$('#fieldset-field_required').hide();
				$('#fieldset-enable_frontedit').hide();
				$('#fieldset-enable_frontedit').prev('h2').hide();
			} else {
				$('#fieldset-field_instructions').show();
				$('#fieldset-field_required').show();
				$('#fieldset-enable_frontedit').show();
				$('#fieldset-enable_frontedit').prev('h2').show();
			}
		});
		$("input[name='field_type']").trigger("change");
	});

})(jQuery);
