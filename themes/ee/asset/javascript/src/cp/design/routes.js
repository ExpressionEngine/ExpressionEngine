/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {
	$('.grid-publish').find('.toolbar .add a').parents('ul.toolbar').remove();
	$('.grid-publish').removeClass('grid-publish');
	$('.grid-input-form').removeClass('grid-input-form');

	$('#routes').on('grid:addRow', function(e, el) {
		$(el).addClass('setting-field');
		Dropdown.renderFields($(el).find('td').eq(1));
	});
});
