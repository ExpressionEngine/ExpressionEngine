/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {

	$('fieldset :input:hidden').attr('disabled', true);

	$('select[name="m_field_type"]').on('change', function() {
		$('fieldset :input:hidden').attr('disabled', true);
		$('fieldset input[type=hidden], fieldset :input:visible').attr('disabled', false);
	});

});
