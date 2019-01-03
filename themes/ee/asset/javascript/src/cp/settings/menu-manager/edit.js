/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

(function($) {

var options = {
	onFormLoad: function(modal) {
		EE.grid(document.getElementById('submenu'), EE.grid_field_settings['submenu'])
	}
}

var menuSetItems = new MutableSelectField('menu_items', Object.assign(EE.menuSetsItem, options))

})(jQuery);
