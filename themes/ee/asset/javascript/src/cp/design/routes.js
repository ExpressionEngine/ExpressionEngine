/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	$('.grid-publish').find('.toolbar .add a').parents('ul.toolbar').remove();
	$('.grid-publish').removeClass('grid-publish');
	$('.grid-input-form').removeClass('grid-input-form');

	$('#routes').on('grid:addRow', function(e, el) {
		$(el).addClass('setting-field');
		Dropdown.renderFields($(el).find('td').first());
	});
});
