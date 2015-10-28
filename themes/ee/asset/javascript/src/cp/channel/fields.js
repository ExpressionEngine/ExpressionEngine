/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.1.0
 * @filesource
 */

$(document).ready(function () {

	$('select[name="field_type"]').on('change', function() {
		$('input:hidden, select:hidden').attr('disabled', true);
		$('input[type=hidden], input:visible, select:visible').attr('disabled', false);
	}

});