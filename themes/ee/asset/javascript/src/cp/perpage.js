/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {
	$('input[name="perpage"]').on('change keyup', function(e){
		var threshold = parseInt($(this).data('threshold'));
		var value = parseInt($(this).val());
		if (value >= threshold) {
			if ($('#threshold-warning').length == 0) {
				var html = '<div id="threshold-warning" class="alert warn">';
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
