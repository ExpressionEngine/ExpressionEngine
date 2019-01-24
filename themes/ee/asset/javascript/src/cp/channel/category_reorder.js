/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('.nestable').nestable({
		listNodeName: 'ul',
		listClass: 'tbl-list',
		itemClass: 'tbl-list-item',
		rootClass: 'nestable',
		dragClass: 'drag-tbl-row',
		handleClass: 'reorder',
		placeElement: $('<li><div class="tbl-row drag-placeholder"><div class="none"></div></div></li>'),
		expandBtnHTML: '',
		collapseBtnHTML: '',
		maxDepth: 10
	}).on('change', function() {

		$.ajax({
			url: EE.cat.reorder_url,
			data: {'order': $('.nestable').nestable('serialize') },
			type: 'POST',
			dataType: 'json',
			error: function(xhr, text, error) {
				// Let the user know something went wrong
				if ($('body > .banner').size() == 0) {
					$('body').prepend(EE.alert.reorder_ajax_fail);
				}
			}
		});
	});

	// This is probably best in a plugin or common area as
	// we have more of these; keeping it here for now while
	// we assess the requirements for new table lists
	$('.tbl-list .check-ctrl input').click(function(){

		// Check/uncheck the children of this category
		$(this).parents('.tbl-list-item')
			.first()
			.find('.tbl-list .check-ctrl input')
			.prop('checked', $(this).is(':checked'))
			.trigger('change');

		// If we're unchecking something, make sure all its
		// parents are also unchecked
		if ( ! $(this).is(':checked')) {
			$(this).parents('.tbl-list-item')
				.find('> .tbl-row > .check-ctrl input')
				.prop('checked', false)
				.trigger('change');
		}
	});
});

})(jQuery);
