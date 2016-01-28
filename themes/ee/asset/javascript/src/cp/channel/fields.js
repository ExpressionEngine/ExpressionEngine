/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		http://ellislab.com
 * @since		Version 3.1.0
 * @filesource
 */

$(document).ready(function () {

	$('fieldset :input:hidden').attr('disabled', true);

	$('select[name="field_type"]').on('change', function() {
		$('input:hidden, select:hidden, textarea:hidden').attr('disabled', true);
		$('input[type=hidden], input:visible, select:visible, textarea:visible').attr('disabled', false);
	});

});
