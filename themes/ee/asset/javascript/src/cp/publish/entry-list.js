/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

	var replaceData = function(data) {
		$('#edit-table').html(data.html);

		$('input[name="search"]').closest('form').attr('action', data.url);

		if (jQuery().toggle_all) {
			$('table').toggle_all();
		}

		window.history.pushState(null, '', data.url);
	}

	// Submitting the search form
	$('input[name="search"]').closest('form').on('submit', function(event) {
		$.ajax({
			url: $(this).attr('action'),
			data: $(this).serialize(),
			type: 'POST',
			dataType: 'json',
			success: replaceData
		});

		event.preventDefault();
	});

	// Typing into the search form
	$('input[name="search"]').on('interact', _.debounce(function() {
		if (location.protocol === 'https:' &&
			navigator.userAgent.indexOf('Safari') > -1) {
			return;
		}
		$(this).closest('form').submit();
	}, 150));

	// Selecting a channel filter
	$('body').on('click', 'form > .filters .sub-menu a, .filters .filter-clear a, .paginate ul li a', function(event) {

		var search = $('input[name="search"]').serialize();

		$.ajax({
			url: $(this).attr('href') + '&' + search,
			type: 'GET',
			dataType: 'json',
			success: replaceData
		});

		event.preventDefault();
	});

	// ==================================
	// column filter custom view selector
	// ==================================

	$('.filter-bar #columns_view_choose').on('change', function() {
		var view = $(this).val();

		$('#columns_view_new, #columns_view_options').hide();

		if (view === 'NEW') {
			$('#columns_view_new').show();
		} else if (view !== '') {
			$('#columns_view_options').show();
		}
	});

	$('#columns_view_switch').on('click', function() {
		var view = $('#columns_view_choose').val();

		if (view !== '') {
			window.location.href = view;
		}
	});

	// $('body').on('change', 'div[rev=toggle-columns] input[name="columns[]"]', function(e) {
	// 	var form = $(this).closest('form')
	// 	$.ajax({
	// 		url: form.attr('actions'),
	// 		data: form.serialize(),
	// 		type: 'GET',
	// 		dataType: 'json',
	// 		success: replaceData
	// 	})
	// })

	$('body').on('click', '.filter-item__link--save', function(e) {

		e.preventDefault();
		var url;

		if (typeof($(this).attr('href'))!='undefined' && $(this).attr('href')!='' && $(this).attr('href')!='#') {
			url = $(this).attr('href');
		} else if ($('#columns_view_choose').val()=='NEW') {
			url = EE.viewManager.createUrl + '&' + $(this).closest('form').find('input[name="columns[]"]').serialize()
		} else {
			url = EE.viewManager.editUrl.replace('###', $('#columns_view_choose option:selected').data('id'))
		}

		EE.cp.ModalForm.openForm({
			url: url,
			createUrl: EE.viewManager.createUrl,
			load: function (modal) {
				SelectField.renderFields(modal)
			},
			success: function(result) {
				if (result.redirect) {
					window.location = result.redirect
				}
				console.log(result)
			}
		})
	})
});
