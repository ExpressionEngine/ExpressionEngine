/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('*[data-group-toggle]').each(function(index, el) {
		EE.cp.form_group_toggle(this);
	});

});

EE.cp.form_group_toggle = function(element) {

	var config = $(element).data('groupToggle'),
		value  = $(element).val();

	$.each(config, function (key, data) {
		$('*[data-group="'+data+'"]').toggle(key == value);
	})
}

})(jQuery);