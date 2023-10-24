/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('.js-nestable-categories').nestable({
		listNodeName: 'ul',
		listClass: 'list-group.list-group--nested',
		itemClass: 'js-nested-item',
		rootClass: 'js-nestable-categories',
		dragClass: 'list-group--dragging',
		handleClass: 'list-item__handle',
		placeElement: $('<li><div class="tbl-row drag-placeholder"><div class="none"></div></div></li>'),
		expandBtnHTML: '<button class="expand-btn" data-action="expand">Expand</button>',
		collapseBtnHTML: '<button class="expand-btn collapse-btn" data-action="collapse">Collapse</button>',
		collapsedClass: 'dd-collapsed',
		maxDepth: 10
	}).on('change', function() {

		$.ajax({
			url: EE.cat.reorder_url,
			data: {'order': $('.js-nestable-categories').nestable('serialize') },
			type: 'POST',
			dataType: 'json',
			error: function(xhr, text, error) {
				// Let the user know something went wrong
				if ($('body > .banner').length == 0) {
					$('body').prepend(EE.alert.reorder_ajax_fail);
				}
			}
		});
	});

	// This is probably best in a plugin or common area as
	// we have more of these; keeping it here for now while
	// we assess the requirements for new table lists
	$('.list-group .list-item__checkbox input').click(function(){

		// Check/uncheck the children of this category
		$(this).parents('.js-nested-item')
			.first()
			.find('.list-group .list-item__checkbox input')
			.prop('checked', $(this).is(':checked'))
			.trigger('change');

		// If we're unchecking something, make sure all its
		// parents are also unchecked
		if ( ! $(this).is(':checked')) {
			$(this).parents('.js-nested-item')
				.find('> .tbl-row > .check-ctrl input')
				.prop('checked', false)
				.trigger('change');
		}
	});
});

})(jQuery);
