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

"use strict";

(function ($) {
	$(document).ready(function () {
		// Single Relationship:
		//   When the radio button is clicked, copy the chosen data into the
		//   div.relate-wrap-chosen area
		$('div.publish').on('click', '.relate-wrap input:radio', function (e) {
			var relationship = $(this).closest('.relate-wrap'),
				label = $(this).closest('label'),
				input_name = relationship.find('.input-name').attr('name'),
				chosen = $(this).closest('.scroll-wrap')
					.data('template')
					.replace(/{entry-id}/g, $(this).val())
					.replace(/{entry-title}/g, label.data('entry-title'))
					.replace(/{channel-title}/g, label.data('channel-title'));

			relationship.find('.relate-wrap-chosen .no-results')
				.closest('label')
				.addClass('hidden')
				.removeClass('block');
			relationship.find('.relate-wrap-chosen .relate-manage').remove();
			relationship.find('.relate-wrap-chosen').first().append(chosen);
			relationship.find('.relate-wrap-chosen label.chosen').append(
				$('<input/>', {
					type: 'hidden',
					name: input_name,
					value: $(this).val()
				})
			);
			relationship.removeClass('empty');
		});

		// Multiple Relationships
		//   When checkbox is clicked, copy the chosen data into the second
		//   div.relate-wrap div.scroll-wrap area
		$('div.publish').on('click', '.relate-wrap input:checkbox', function (e) {
			var relationship = $(this).closest('.relate-wrap')
				.siblings('.relate-wrap')
				.first();

			var label = $(this).closest('label'),
				input_name = $(this).closest('.relate-wrap').find('.input-name').attr('name');

			// jQuery will decode encoded HTML in a data attribute,
			// so we'll use this trick to keep it encoded
			var encoded_title = $('<div/>').text(label.data('entry-title')).html();

			var chosen = $(this).closest('.scroll-wrap')
				.data('template')
			.replace(/{entry-id}/g, $(this).val())
			.replace(/{entry-title}/g, encoded_title)
			.replace(/{entry-title-lower}/g, encoded_title.toLowerCase())
			.replace(/{channel-title}/g, label.data('channel-title'));

			// If the checkbox was unchecked run the remove event
			if ($(this).prop('checked') == false) {
				relationship.find('.scroll-wrap a[data-entry-id=' + $(this).val() + ']').click();
				return;
			}

			relationship.find('.scroll-wrap .no-results').addClass('hidden');
			relationship.removeClass('empty');
			relationship.find('.scroll-wrap').first().append(chosen);
			relationship.find('.scroll-wrap label')
				.last()
				.data('entry-title', encoded_title)
				.data('channel-id', label.data('channel-id'))
				.data('channel-title', label.data('channel-title'))
				.prepend('<span class="relate-reorder"></span>')
				.append(
					$('<input/>', {
						type: 'hidden',
						name: input_name,
						value: $(this).val()
					})
				);

			$(this).siblings('input:hidden')
				.val(relationship.find('.scroll-wrap label').length);
		});

		// Removing Relationships
		$('div.publish').on('click', '.relate-wrap .relate-manage a', function (e) {
			var choices = $(this).closest('.relate-wrap');
			var chosen = $(this).closest('.relate-wrap');

			// Is this a multiple relationship?
			if (choices.hasClass('w-8')) {
				choices = choices.siblings('.relate-wrap').first();
			}
			else
			{
				choices.addClass('empty');
			}

			choices.find('.scroll-wrap :checked[value=' + $(this).data('entry-id') + ']')
				.attr('checked', false)
				.parents('.choice')
				.removeClass('chosen');

			$(this).closest('label').remove();

			if (chosen.find('.relate-manage').length == 0) {
				if (chosen.hasClass('w-8')) {
					chosen.addClass('empty')
						.find('.no-results')
						.removeClass('hidden');
				} else {
					chosen.find('.relate-wrap-chosen .no-results')
						.closest('label')
						.removeClass('hidden')
						.addClass('block');
				}
			}

			e.preventDefault();
		});

		var ajaxTimer,
			ajaxRequest;

		function ajaxRefresh(elem, search, channelId, delay) {
			var settings = $(elem).closest('.relate-wrap').data('settings'),
				data = {};

			data['settings'] = settings;
			data['search'] = search;
			data['channel_id'] = channelId;

			// Cancel the last AJAX request
			clearTimeout(ajaxTimer);
			if (ajaxRequest) {
				ajaxRequest.abort();
			}

			ajaxTimer = setTimeout(function() {
				ajaxRequest = $.ajax({
					url: EE.relationship.filter_url,
					data: $.param(data),
					type: 'POST',
					dataType: 'json',
					success: function(ret) {
						var scroll_wrap = $(elem).closest('.relate-wrap').find('.scroll-wrap').first();

						populateEntryList(scroll_wrap, ret);
					}
				});
			}, delay);

		}

		/**
		 * Populates a given container with entry choice elements
		 *
		 * @param	{jQuery object}	scroll_wrap	Parent scroll-wrap container for entries
		 * @param	{array}			entries		Array of entry objects
		 */
		function populateEntryList(scroll_wrap, entries) {
			var relate_wrap = scroll_wrap.closest('.relate-wrap'),
				multiple = relate_wrap.hasClass('w-8'),
				no_results = scroll_wrap.find('.no-results'),
				input_name = scroll_wrap.find('.input-name').attr('name');

			no_results.addClass('hidden');
			scroll_wrap.find('label').remove();

			if (entries.length == 0) {
				no_results.removeClass('hidden');
			}

			for (i in entries) {
				scroll_wrap.append(
					makeElementForEntry(entries[i], input_name, multiple)
				);
			}
		}

		/**
		 * Constructs an entry choice element to display in the choices pane
		 * of a Relationship field
		 *
		 * @param	{object}	entry		JSON object of entry details
		 * @param	{string}	input_name	Input name
		 * @param	{boolean}	multiple	Whether or not this is a multi-relationship field
		 */
		function makeElementForEntry(entry, input_name, multiple) {
			var checked = $('input[name="'+input_name+'"][value='+entry.entry_id+']').length > 0,
				checked_class = checked ? ' chosen' : '',
				choice_element = multiple ? 'checkbox' : 'radio';

			var label = $('<label/>', {
				'class': 'choice block' + checked_class,
				'data-channel-id': entry.channel_id,
				'data-channel-title': entry.channel_name,
				'data-entry-title': entry.title,
			});

			var choice = $('<input/>', {
				type: choice_element,
				name: choice_element == 'checkbox' ? '' : input_name+'[dummy][]',
				value: entry.entry_id
			});

			if (checked) {
				choice.attr('checked', 'checked');
			}

			var channel_title = $('<i/>').html(' &mdash; ' + entry.channel_name);

			return label.append(choice).append(' ' + entry.title).append(channel_title);
		}

		// Filter by Channel
		$('div.publish').on('click', '.relate-wrap .relate-actions .filters a[data-channel-id]', function (e) {
			var search = $(this).closest('.relate-wrap').find('.relate-search').val(),
				link = $(this).closest('.filters').find('a.has-sub'),
				channel_id = $(this).data('channel-id'),
				span = $('<span/>', {
					'class': 'faded',
					'data-channel-id': channel_id
				}).html(' ('+$(this).text()+')');

			link.find('span').remove();
			link.append(span);

			ajaxRefresh(this, search, channel_id, 0);

			$(document).click(); // Trigger the code to close the menu
			e.preventDefault();
		});

		// Search Relationships
		$('div.publish').on('interact', '.relate-wrap.col.w-8[data-field] .relate-search, .relate-wrap.col.w-16 .relate-search', function (e) {
			var channelId = $(this).closest('.relate-actions')
				.find('.filters .has-sub .faded')
				.data('channel-id');

			ajaxRefresh(this, $(this).val(), channelId, 300);
		});

		// Filtering of chosen entries in a multiple relationships UI
		$('div.publish').on('interact', '.relate-wrap.col.w-8.last .relate-search', function (e) {
			var relate_wrap = $(this).closest('.relate-wrap'),
				labels = relate_wrap.find('label.chosen'),
				no_results = relate_wrap.find('.no-results');

			no_results.addClass('hidden');

			// No search terms, reset
			if ( ! this.value)
			{
				labels.removeClass('hidden');
				no_results.toggleClass('hidden', relate_wrap.find('label.chosen:visible').size() != 0);
				return;
			}

			// Do the filtering
			labels.removeClass('hidden')
				.not('label[data-search*="' + this.value.toLowerCase() + '"]')
				.addClass('hidden');

			if (relate_wrap.find('label.chosen:visible').size() == 0) {
				no_results.removeClass('hidden');
			}
		});

		// Sortable!
		var sortable_options = {
			axis: 'y',
			cursor: 'move',
			handle: '.relate-reorder',
			items: 'label',
		};

		$('.w-8.relate-wrap .scroll-wrap').sortable(sortable_options);

		Grid.bind('relationship', 'display', function(cell) {
			$('.w-8.relate-wrap .scroll-wrap', cell).sortable(sortable_options);
		});
	});
})(jQuery);
