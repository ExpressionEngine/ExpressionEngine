/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.1.0
 * @filesource
 */

$(document).ready(function () {

	$('fieldset :input:hidden').attr('disabled', true);
	$('fieldset:visible input[type=hidden]').attr('disabled', false);

	$('select[name="field_type"]').on('change', function() {
		$(':input:hidden').attr('disabled', true);
		$('fieldset:visible input[type=hidden], input:visible, select:visible, textarea:visible').attr('disabled', false);
	});

});
