/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

$('div[data-input-value^="categories["]').each(function(index, element) {
	var groupId = $(element).data('inputValue')
		.replace('categories[cat_group_id_', '').replace(']', '')

	var settings = {
		createUrl: EE.categories.createUrl.replace('###', groupId),
		editUrl: EE.categories.editUrl.replace('###', groupId + '/###'),
		removeUrl: EE.categories.removeUrl,
		fieldUrl: EE.categories.fieldUrl.replace('###', groupId),
		onFormLoad: function(modal) {
			if (modal.find('form').attr('action').includes('create')) {
				$('input[name=cat_name]', modal).bind("keyup keydown", function() {
					$(this).ee_url_title('input[name=cat_url_title]', true);
				})
			}
		}
	}

	new MutableSelectField($(element).data('inputValue'), settings)
})

})(jQuery);
