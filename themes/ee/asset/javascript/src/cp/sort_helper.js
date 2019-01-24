/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Fixes an issue in jQuery UI's Sortable implementation of it's
 * tolerance: 'intercect' option not working correctly; this fix
 * ensures once an item overlaps by 50%, the sort happens, and does
 * not depend on the position of the cursor
 */
EE.sortable_sort_helper = function(e, ui)
{
	// Get the axis to determine if we're working with heights or widths
	var axis = ($(this).sortable('option', 'axis') == false)
			? 'y' : $(this).sortable('option', 'axis'),
		container	= $(this),
		placeholder = container.children('.ui-sortable-placeholder:first'),
		helperSize	= (axis == 'y') ? ui.helper.outerHeight() : ui.helper.outerWidth(),
		helperPos	= (axis == 'y') ? ui.position.top : ui.position.left,
		helperEnd	= helperPos + helperSize;

	// Ensure placeholder is the same height as helper for
	// calculations to work
	placeholder.height(ui.helper.outerHeight());

	container.children(':visible').each(function ()
	{
		var item = $(this);

		if ( ! item.hasClass('ui-sortable-helper')
			&& ! item.hasClass('ui-sortable-placeholder'))
		{
			var itemSize = (axis == 'y') ? item.outerHeight() : item.outerWidth(),
				itemPos	 = (axis == 'y') ? item.position().top : item.position().left,
				itemEnd	 = itemPos + itemSize,
				tolerance = Math.min(helperSize, itemSize) / 2;

			if (helperPos > itemPos && helperPos < itemEnd)
			{
				var distance  = helperPos - itemPos;

				if (distance < tolerance)
				{
					placeholder.insertBefore(item);
					container.sortable('refreshPositions');
					return false;
				}
			}
			else if (helperEnd < itemEnd && helperEnd > itemPos)
			{
				var distance  = itemEnd - helperEnd;

				if (distance < tolerance)
				{
					placeholder.insertAfter(item);
					container.sortable('refreshPositions');
					return false;
				}
			}
		}
	});
};
