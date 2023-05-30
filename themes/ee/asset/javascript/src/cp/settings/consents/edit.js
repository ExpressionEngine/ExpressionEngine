/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	var confirmModal = function(e) {
		var modal = $('.modal-confirm-new-version'),
		    confirmedActionButton = e.target;

		e.preventDefault();
		modal.trigger('modal:open');

		$('.modal .button').one('click', function(e) {
			e.preventDefault();
			modal.trigger('modal:close');

			$('form').off('submit', confirmModal);
			$('button[name="submit"]').off('click', confirmModal);
			$(confirmedActionButton).click();
		});
	};

	$('form').on('submit', confirmModal);
	$('button[name="submit"]').on('click', confirmModal);
});
