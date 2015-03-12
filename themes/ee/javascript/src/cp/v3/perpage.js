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
	$('input[name="perpage"]').on('change keyup', function(e){
		var threshold = parseInt($(this).data('threshold'));
		var value = parseInt($(this).val());
		if (value >= threshold) {
			if ($('#threshold-warning').length == 0) {
				var html = '<div id="threshold-warning" class="alert banner warn">';
				html = html + '<p>' + $(this).data('threshold-text') + '</p>';
				html = html + '<a class="close" href=""></a>';
				html = html + '</div>';

				$('body').prepend(html);
			}
		} else {
			$('#threshold-warning').remove();
		}
	});
});