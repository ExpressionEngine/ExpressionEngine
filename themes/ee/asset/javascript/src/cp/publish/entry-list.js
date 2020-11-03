/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

	var saveDefaultUrl = EE.viewManager.saveDefaultUrl;
	var replaceData = function(data) {
		$('.ee-main__content > .container').html(data.html);

		saveDefaultUrl = data.viewManager_saveDefaultUrl;

		if (jQuery().toggle_all) {
			$('table').toggle_all();
		}

		window.history.pushState(null, '', data.url);
	}

	// Submitting the search form
	$('body').on('submit', '.ee-main__content > .container > .tbl-ctrls > form', function(event) {
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
	$('body').on('keyup', 'input[name="search"]', _.debounce(function() {
		if (location.protocol === 'https:' &&
			navigator.userAgent.indexOf('Safari') > -1) {
			return;
		}
		$(this).closest('form').submit();
	}, 150));

	// Selecting a channel filter
	$('body').on('click', 'form .filter-search-bar .dropdown a.dropdown__link, form .filter-bar .dropdown a.dropdown__link, .filter-bar .filter-bar__button--clear, .pagination li a, .column-sort', function(event) {

		var search = $('input[name="search"]').serialize();

		$.ajax({
			url: $(this).attr('href') + '&' + search,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				replaceData(data);
				sortableColumns();
			}
		});

		event.preventDefault();
	});

	// ==================================
	// column filter custom view selector
	// ==================================

	var saveViewRequest = null;
	var loadViewRequest = null;
	var viewColumns = [];
	var viewColumnsChanged = false;
	$('body').on('change', '.filter-search-bar div[rev="toggle-columns"] input', function(e){
		e.preventDefault();
		if (saveViewRequest) {
			saveViewRequest.abort();
		}
		if (loadViewRequest) {
			loadViewRequest.abort();
		}

		$('.filter-search-bar div[rev="toggle-columns"] input').each(function(el){
			viewColumnsChanged = true;
			if ($(this).is(':checked')) {
				viewColumns.push($(this).val());
			}
		});
	});

	$('body').on('click', function(e){
		if ( $(e.target).closest('.filter-search-bar div[rev="toggle-columns"]').length === 0) {
			saveView();
		}
	});

	//the above does not 'catch' button click, thus we need this extra
	$('body').on('click', '.js-dropdown-toggle', function(e){
		saveView();
	});

	function saveView() {
		if (viewColumnsChanged) {
			if (saveViewRequest) {
				saveViewRequest.abort();
			}
			if (loadViewRequest) {
				loadViewRequest.abort();
			}

			var _form = $('.filter-search-bar div[rev="toggle-columns"]').closest('form');
			var _data = $('input[name!="columns[]"]', _form).serialize();

			saveViewRequest = $.ajax({
				url: saveDefaultUrl,
				data: _form.serialize(),
				type: 'POST',
				dataType: 'json',
				success: function() {
					viewColumnsChanged = false;
					saveViewRequest = null;
					loadViewRequest = $.ajax({
						url: _form.attr('action'),
						data: _data,
						type: 'POST',
						dataType: 'json',
						success: function(data) {
							loadViewRequest = null;
							replaceData(data);
							sortableColumns();
						},
						error: function(e) {
							//do nothing
						}
					});
				},
				error: function(e) {
					//do nothing
				}
			});
		}
	}

	// Make the columns sortable
	function sortableColumns() {
		$('.filter-search-bar div[rev="toggle-columns"]').sortable({
			containment: false,
			handle: '.dropdown-reorder', // Set drag handle to the top box
			items: '.dropdown__item',			// Only allow these to be sortable
			sort: function(){},	// Custom sort handler
			cancel: '.no-drag',
			start: function (event, ui) {
				viewColumnsChanged = true;
			},
			stop: function (event, ui) {
				//saveView();
			}
		});
	}



	$('.filter-search-bar #columns_view_choose').on('change', function() {
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
			}
		})
	})

	sortableColumns();
});
