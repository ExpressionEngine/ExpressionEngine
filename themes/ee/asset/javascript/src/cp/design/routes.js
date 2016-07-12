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
	$('.grid-publish').find('.toolbar .add a').parents('ul.toolbar').remove();
	$('.grid-publish').removeClass('grid-publish');
	$('.grid-input-form').removeClass('grid-input-form');


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

	$('#routes').on('grid:addRow', function(e, el) {
		$(el).addClass('setting-field');
	});
});