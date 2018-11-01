/*!
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {
	var confirmModal = function(e) {
		var modal = $('.modal-confirm-new-version'),
		    confirmedActionButton = e.target;

		e.preventDefault();
		modal.trigger('modal:open');

		$('.modal input.btn').one('click', function(e) {
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
