/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

$(document).ready(function () {
	$('.tbl-action a.btn').click(function (e) {
		e.preventDefault();

		var blankRoute = $(this).closest('table').find('tr.last').eq(0);
		var newRoute = blankRoute.clone();

		newRoute.removeClass('hidden');
		newRoute.removeClass('last');

		newRoute.html(
			newRoute.html().replace(
				RegExp('new_route_[0-9]{1,}', 'g'),
				'new_route_' + EE.new_route_index
			)
		);

		blankRoute.before(newRoute);
		EE.new_route_index++;
	})

	$('table').on('change', 'select', function (e) {
		var group = $('option:selected', this).closest('optgroup').attr('label');
		$(this).closest('td').next().html(group);

		$('option:disabled').removeAttr('disabled');

		$('option:selected').each(function (index, element) {
			if (element.value) {
				$('option[value=' + element.value + ']:not(:selected)').attr('disabled', 'disabled');
			}
		});
	});

	$('.toolbar .remove a').on('click', function(e) {
		$(this).closest('tr').find('input[type=text]').val('');
		$(this).closest('tr').hide();
	});
});