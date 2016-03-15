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

	$('select[name="m_field_type"]').on('change', function() {
		$('fieldset :input:hidden').attr('disabled', true);
		$('fieldset input[type=hidden], fieldset :input:visible').attr('disabled', false);
	});

});
