/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	$('.sidebar .folder-list .remove.m-link').click(function (e) {
		var modalIs = '.' + $(this).attr('rel');

		$(modalIs + " .checklist").html(''); // Reset it
		$(modalIs + " .checklist").append('<li>' + $(this).data('confirm') + '</li>');
		$(modalIs + " input[name='group_name']").val($(this).data('group_name'));

		e.preventDefault();
	})
});
