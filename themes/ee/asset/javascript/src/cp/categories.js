/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

(function($) {

$('div[data-input-value^="categories["]').each(function(index, element) {
	var groupId = $(element).data('inputValue').replace('categories[cat_group_id_', '').replace(']', '')
	var settings = {
		createUrl: EE.categories.createUrl.replace('###', groupId),
		editUrl: EE.categories.editUrl.replace('###', groupId + '/###'),
		removeUrl: EE.categories.removeUrl
	}
	new MutableSelectField($(element).data('inputValue'), settings)
})

})(jQuery);
