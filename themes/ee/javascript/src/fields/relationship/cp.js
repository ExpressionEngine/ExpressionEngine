/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
			var chosen = $(this).data('template')
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
			var chosen = $(this).data('template')
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
		});

		// Removing Relationships
		$('.relate-wrap').on('click', '.relate-manage a', function (e) {
			var relationship = $(this).closest('.relate-wrap');

			// Is this a multiple relationship?
			if (relationship.hasClass('w-8')) {
				relationship = relationship.siblings('.relate-wrap').first();
			}

			relationship.find('.scroll-wrap :checked[value=' + $(this).data('entry-id') + ']')
				.attr('checked', false);

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
			var channelId = $(this).data('channel-id');
			var matchesSearchValue = true;
			var searchText = $(this).closest('.relate-actions')
				.find('.relate-search')
				.first()
				.data('channel-id', channelId)
				.val();

			$(this).closest('.filters').find('a.has-sub .faded').text('(' + $(this).text() + ')');

			$(this).closest('.relate-wrap').find('.scroll-wrap label').each(function() {
				if (searchText) {
					matchesSearchValue = ($(this).data('entry-title').indexOf(searchText) > -1);
				}

				if ($(this).data('channel-id') == channelId && matchesSearchValue)
				{
					$(this).show();
				}
				else
				{
					$(this).hide();
				}
			});

			$(document).click(); // Trigger the code to close the menu
			e.preventDefault();
		});

		// Search Relationships
		$('.relate-wrap .relate-actions .relate-search').on('interact', function (e) {
			var searchText = $(this).val();
			var matchesChannelFilter = true;
			var channelId = $(this).data('channel-id');

			$(this).closest('.relate-wrap').find('.scroll-wrap label').each(function() {
				if (channelId)
				{
					matchesChannelFilter = ($(this).data('channel-id') == channelId);
				}

				if ($(this).data('entry-title').indexOf(searchText) > -1 && matchesChannelFilter)
				{
					$(this).show();
				}
				else
				{
					$(this).hide();
				}
			});
		});
	});
})(jQuery);
