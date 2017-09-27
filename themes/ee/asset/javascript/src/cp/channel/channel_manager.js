/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

(function($) {

var fieldGroupsForm = new MutableSelectField('field_groups', EE.channelManager.fieldGroup)

var options = {
	onFormLoad: function(modal) {
		ChannelManager.fireEvent('fieldModalDisplay', modal)

		$('input[name=field_label]', modal).bind("keyup keydown", function() {
			$(this).ee_url_title('input[name=field_name]', true);
		});
	}
}

var fieldsForm = new MutableSelectField('custom_fields', Object.assign(EE.channelManager.fields, options))

var catGroupsForm = new MutableSelectField('cat_group', EE.channelManager.catGroup)

})(jQuery);
