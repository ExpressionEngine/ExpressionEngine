/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	$('body').on('change keyup', 'input[name="perpage"]', function(e){
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
