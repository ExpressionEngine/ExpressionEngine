/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

$(document).ready(function () {
	$('button.submit').click(function () {
		$(".modal-confirm-all .checklist").html(''); // Reset it
		$('td input:checked').each(function() {
			$(".modal-confirm-all .checklist").append('<li>' + $(this).attr('data-confirm') + '<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '"></li>');
		});
		$(".modal-confirm-all .checklist li:last").addClass('last');
	});
});