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
		$('div.publish').on('click', '.relate-wrap input:radio', function (e) {
			var relationship = $(this).closest('.relate-wrap');
			var label = $(this).closest('label');
			var chosen = $(this).closest('.scroll-wrap')
				.data('template')
				.replace(/{entry-id}/g, $(this).val())
				.replace(/{entry-title}/g, label.data('entry-title'))
				.replace(/{channel-title}/g, label.data('channel-title'));

			relationship.find('.relate-wrap-chosen .no-results')
				.closest('label')
				.hide()
				.removeClass('block');
			relationship.find('.relate-wrap-chosen .relate-manage').remove();
			relationship.find('.relate-wrap-chosen').first().append(chosen);
			relationship.removeClass('empty');
		});

		// Multiple Relationships
		//   When checkbox is clicked, copy the chosen data into the second
		//   div.relate-wrap div.scroll-wrap area
		$('div.publish').on('click', '.relate-wrap input:checkbox', function (e) {
			var relationship = $(this).closest('.relate-wrap')
				.siblings('.relate-wrap')
				.first();

			var label = $(this).closest('label');
			var chosen = $(this).closest('.scroll-wrap')
				.data('template')
			.replace(/{entry-id}/g, $(this).val())
			.replace(/{entry-title}/g, label.data('entry-title'))
			.replace(/{channel-title}/g, label.data('channel-title'));

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
				.removeClass('chosen')
				.find('input:hidden')
				.val(0);

			choices.find('.scroll-wrap input[type="hidden"][value=' + $(this).data('entry-id') + ']')
				.remove();

			$(this).closest('label').remove();

			if (chosen.find('.relate-manage').length == 0) {
				if (chosen.hasClass('w-8')) {
					chosen.addClass('empty')
						.find('.no-results')
						.show();
				} else {
					chosen.find('.relate-wrap-chosen .no-results')
						.closest('label')
						.show()
						.removeClass('hidden')
						.addClass('block');
				}
			}

			e.preventDefault();
		});

		var ajaxTimer,
			ajaxRequest;

		function ajaxRefresh(elem, channelId, delay) {
			var field = $(elem).closest('fieldset').find('div.col.last').eq(0),
				data = $(elem).closest('fieldset').serialize(),
				url = EE.publish.field.URL + '/' + $(field).find('.relate-wrap').data('field'),
				name = $(elem).attr('name');

			if (field.length == 0) {
				field = $(elem).closest('td');

				var row_id = $(field).data('row-id') ? $(field).data('row-id') : 0;

				data = $(field).find('input').serialize() + '&column_id=' + $(field).data('column-id') + '&row_id=' + row_id;
				url = EE.publish.field.URL + '/' + $(elem).closest('table').attr('id');
			}

			if (channelId)
			{
				data += '&channel=' + channelId;
			}

			// Cancel the last AJAX request
			clearTimeout(ajaxTimer);
			if (ajaxRequest) {
				ajaxRequest.abort();
			}

			ajaxTimer = setTimeout(function() {
				ajaxRequest = $.ajax({
					url: url,
					data: data,
					type: 'POST',
					dataType: 'json',
					success: function(ret) {
						$(field).html(ret.html);

						// Set focus back to current search field and place cursor at the end
						var searchField = $('input[name='+name+']', field).focus(),
							tmpStr = searchField.val();
						searchField.val('');
						searchField.val(tmpStr);

						$('.w-8.relate-wrap .scroll-wrap', field).sortable(sortable_options);
					}
				});
			}, delay);

		}

		// Filter by Channel
		$('div.publish').on('click', '.relate-wrap .relate-actions .filters a[data-channel-id]', function (e) {
			ajaxRefresh(this, $(this).data('channel-id'), 0);

			$(document).click(); // Trigger the code to close the menu
			e.preventDefault();
		});

		// Search Relationships
		$('div.publish').on('interact', '.relate-wrap .relate-actions .relate-search', function (e) {
			var channelId = $(this).closest('.relate-actions').find('.filters .has-sub .faded').data('channel-id');

			// In Grids, this field got its name reset
			if ($(this).attr('name').indexOf('search_related') != -1) {
				$(this).attr('name', 'search_related');
			} else {
				$(this).attr('name', 'search');
			}

			ajaxRefresh(this, channelId, 150);
		});

		// Sortable!
		var sortable_options = {
			axis: 'y',
			cursor: 'move',
			handle: '.relate-reorder',
			items: 'label',
		};

		$('.w-8.relate-wrap .scroll-wrap').sortable(sortable_options);

		$('.publish form').on('submit', function (e) {
			$('.w-8.relate-wrap .scroll-wrap').each(function() {
				var label;
				var relationship = $(this).closest('.relate-wrap')
					.siblings('.relate-wrap').first();

				// Adding a new grid row will enable all the disabled sort fields
				$(this).find('input:hidden[name$="[sort][]"]').attr('disabled', 'disabled');

				var i = 1;
				$(this).find('label.relate-manage').each(function () {
					label = relationship.find('input[name$="[data][]"][value=' + $(this).data('entry-id') + ']').closest('label');
					var sort = label.find('input:hidden[name$="[sort][]"]').first();
					sort.removeAttr('disabled');
					sort.val(i);
					i++;
				});
			});
		});
	});
})(jQuery);
