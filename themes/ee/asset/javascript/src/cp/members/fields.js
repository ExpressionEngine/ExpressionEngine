/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
