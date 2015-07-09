/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

"use strict";

(function ($) {
	$(document).ready(function () {
		// Single Relationship:
		//   When the radio button is clicked, copy the chosen data into the
		//   div.relate-wrap-chosen area
		$('.relate-wrap input:radio').on('click', function (e) {
			var relationship = $(this).closest('.relate-wrap');
			var label = $(this).closest('label');
			var chosen = $(this).closest('.scroll-wrap')
				.data('template')
				.replace('{entry-id}', $(this).val())
				.replace('{entry-title}', label.data('entry-title'))
				.replace('{channel-title}', label.data('channel-title'));

			relationship.find('.relate-wrap-chosen .no-results').hide();
			relationship.find('.relate-wrap-chosen .relate-manage').remove();
			relationship.find('.relate-wrap-chosen').first().append(chosen);
		});

		// Multiple Relationships
		//   When checkbox is clicked, copy the chosen data into the second
		//   div.relate-wrap div.scroll-wrap area
		$('.relate-wrap input:checkbox').on('click', function (e) {
			var relationship = $(this).closest('.relate-wrap')
				.siblings('.relate-wrap')
				.first();

			var label = $(this).closest('label');
			var chosen = $(this).closest('.scroll-wrap')
				.data('template')
				.replace('{entry-id}', $(this).val())
				.replace('{entry-title}', label.data('entry-title'))
				.replace('{channel-title}', label.data('channel-title'));

			// If the checkbox was unchecked run the remove event
			if ($(this).prop('checked') == false) {
				relationship.find('.scroll-wrap a[data-entry-id=' + $(this).val() + ']').click();
				return;
			}

			relationship.find('.scroll-wrap .no-results').hide();
			relationship.removeClass('empty');
			relationship.find('.scroll-wrap').first().append(chosen);
			relationship.find('.scroll-wrap label')
				.last()
				.data('entry-title', label.data('entry-title'))
				.data('channel-id', label.data('channel-id'))
				.data('channel-title', label.data('channel-title'))
				.prepend('<span class="relate-reorder"></span>');

			$(this).siblings('input:hidden')
				.val(relationship.find('.scroll-wrap label').length);
		});

		// Removing Relationships
		$('.relate-wrap').on('click', '.relate-manage a', function (e) {
			var relationship = $(this).closest('.relate-wrap');

			// Is this a multiple relationship?
			if (relationship.hasClass('w-8')) {
				relationship = relationship.siblings('.relate-wrap').first();
			}

			relationship.find('.scroll-wrap :checked[value=' + $(this).data('entry-id') + ']')
				.attr('checked', false)
				.siblings('input:hidden')
				.val(0);

			$(this).closest('label').remove();

			if (relationship.find('.relate-manage').length == 0) {
				if (relationship.hasClass('w-8')) {
					relationship.find('.relate-wrap .no-results').show();
					relationship.find('.relate-wrap').addClass('empty');
				} else {
					relationship.find('.relate-wrap-chosen .no-results').show();
				}
			}

			e.preventDefault();
		});

		// Filter by Channel
		$('.relate-wrap .relate-actions .filters a[data-channel-id]').on('click', function (e) {
			var empty = true;
			var channelId = $(this).data('channel-id');
			var matchesSearchValue = true;
			var searchText = $(this).closest('.relate-actions')
				.find('.relate-search')
				.first()
				.data('channel-id', channelId)
				.val();

			if (channelId) {
				$(this).closest('.filters').find('a.has-sub .faded').text('(' + $(this).text() + ')');
			} else {
				$(this).closest('.filters').find('a.has-sub .faded').text('');
			}

			$(this).closest('.relate-wrap').find('.scroll-wrap label').each(function() {
				if (searchText) {
					matchesSearchValue = ($(this).data('entry-title').toLowerCase().indexOf(searchText.toLowerCase()) > -1);
				}

				if (($(this).data('channel-id') == channelId || ! channelId) && matchesSearchValue) {
					$(this).show();
					empty = false;
				} else {
					$(this).hide();
				}
			});

			if (empty) {
				$(this).closest('.relate-wrap')
					.addClass('empty')
					.find('.no-results')
					.show();

				if (channelId) {
					$(this).closest('.relate-wrap')
						.find('.no-results a.btn, .no-results .filters')
						.hide();

					$(this).closest('.relate-wrap')
						.find('.no-results a.btn[data-channel-id=' + channelId + ']')
						.show();
				} else {
					$(this).closest('.relate-wrap')
						.find('.no-results a.btn')
						.hide();

					$(this).closest('.relate-wrap')
						.find('.no-results .filters')
						.show();
				}
			} else {
				$(this).closest('.relate-wrap')
					.removeClass('empty')
					.find('.no-results')
					.hide();
			}

			$(document).click(); // Trigger the code to close the menu
			e.preventDefault();
		});

		// Search Relationships
		$('.relate-wrap .relate-actions .relate-search').on('interact', function (e) {
			var empty = true;
			var searchText = $(this).val();
			var matchesChannelFilter = true;
			var channelId = $(this).data('channel-id');

			$(this).closest('.relate-wrap').find('.scroll-wrap label').each(function() {
				if (channelId) {
					matchesChannelFilter = ($(this).data('channel-id') == channelId);
				}

				if ($(this).data('entry-title').toLowerCase().indexOf(searchText.toLowerCase()) > -1 && matchesChannelFilter) {
					$(this).show();
					empty = false;
				} else {
					$(this).hide();
				}
			});

			if (empty) {
				$(this).closest('.relate-wrap')
					.addClass('empty')
					.find('.no-results')
					.show();

				if (channelId) {
					$(this).closest('.relate-wrap')
						.find('.no-results a.btn, .no-results .filters')
						.hide();

					$(this).closest('.relate-wrap')
						.find('.no-results a.btn[data-channel-id=' + channelId + ']')
						.show();
				} else {
					$(this).closest('.relate-wrap')
						.find('.no-results a.btn')
						.hide();

					$(this).closest('.relate-wrap')
						.find('.no-results .filters')
						.show();
				}
			} else {
				$(this).closest('.relate-wrap')
					.removeClass('empty')
					.find('.no-results')
					.hide();
			}
		});

		// Sortable!
		$('.w-8.relate-wrap .scroll-wrap').sortable({
			axis: 'y',
			cursor: 'move',
			handle: '.relate-reorder',
			items: 'label',
		});

		$('.publish form').on('submit', function (e) {
			$('.w-8.relate-wrap .scroll-wrap').each(function() {
				var label;
				var relationship = $(this).closest('.relate-wrap')
					.siblings('.relate-wrap').first();

				var i = 1;
				$(this).find('label.relate-manage').each(function () {
					label = relationship.find('input[name$="[data][]"][value=' + $(this).data('entry-id') + ']').closest('label');
					label.find('input:hidden[name$="[sort][]"]').first().val(i);
					i++;
				});
			});
		});
	});
})(jQuery);
