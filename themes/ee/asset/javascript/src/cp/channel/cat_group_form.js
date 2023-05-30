/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

var options = {
	onFormLoad: function(modal) {
		FieldManager.fireEvent('fieldModalDisplay', modal)

		EE.cp.fieldToggleDisable(modal)

		// Only bind ee_url_title for new fields
		if ($('input[name=field_name]').val() == '') {
			$('input[name=field_label]', modal).bind("keyup keydown", function() {
				$(this).ee_url_title('input[name=field_name]', true);
			})
		}
	}
}

var fieldsForm = new MutableSelectField('category_fields', Object.assign(EE.categoryField, options))

})(jQuery);
