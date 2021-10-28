/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

var options = {
	onFormLoad: function(modal) {
		EE.grid(document.getElementById('submenu'), EE.grid_field_settings['submenu'])
	}
}

var menuSetItems = new MutableSelectField('menu_items', Object.assign(EE.menuSetsItem, options))

})(jQuery);
