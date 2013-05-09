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

	container.children().each(function ()
	{
		var item = $(this);

		if ( ! item.hasClass('ui-sortable-helper')
			&& ! item.hasClass('ui-sortable-placeholder'))
		{
			var itemSize = (axis == 'y') ? item.outerHeight() : item.outerWidth(),
				itemPos	 = (axis == 'y') ? item.position().top : item.position().left,
				itemEnd	 = itemPos + itemSize;

			if (helperPos > itemPos && helperPos < itemEnd)
			{
				var tolerance = Math.min(helperSize, itemSize) / 2,
					distance  = helperPos - itemPos;

				if (distance < tolerance)
				{
					placeholder.insertBefore(item);
					container.sortable('refreshPositions');
					return false;
				}

			}
			else if (helperEnd < itemEnd && helperEnd > itemPos)
			{
				var tolerance = Math.min(helperSize, itemSize) / 2,
					distance  = itemEnd - helperEnd;

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