/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

	var searching = null;

	var searchingTimeout = null

	if (typeof(EE.viewManager)!=='undefined') {
		var saveDefaultUrl = EE.viewManager.saveDefaultUrl;
	}
	var form_selector = '.container > .panel > .tbl-ctrls > form';
	var replaceData = function(data) {
		$(form_selector).parents('.container').first().html(data.html);
		$.fuzzyFilter();

		if (typeof(EE.viewManager)!=='undefined') {
			saveDefaultUrl = data.viewManager_saveDefaultUrl;
		}

		if (jQuery().toggle_all) {
			$('table').toggle_all();
		}

		if ($(form_selector).parents('.modal-wrap').length == 0) {
			window.history.pushState(null, '', data.url);
		}
		var searchInput = $(form_selector).find('input[name="filter_by_keyword"]')[0];

		if (typeof(searchInput) !== 'undefined') {
			searchInput.focus();
			searchInput.setSelectionRange(1000, 1000);
		}

		if (typeof(FileField) !== 'undefined') {
			FileField.renderFields();
		}
	}

	function searchEntries(type = 'GET', url = null) 
	{
		if (searching) {
			searching.abort();
		}

		var _form = $(form_selector);

		if (url === null) {
			url = typeof(_form.data('search-url'))!='undefined' ? _form.data('search-url') : _form.attr('action');
		}
		
		var data = {};
		if (type != 'GET') {
			data = $('input[name!="columns[]"]', _form).serialize();
		}
		searching = $.ajax({
			url: url,
			type: type,
			data: data,
			dataType: 'json',
			error: function(response) {
				searching = null;
			},
			success: function(response) {
				searching = null;
				replaceData(response);
				sortableColumns();
				ddFileToNotEmptyTable();
				if ( ($('.f_manager-wrapper tbody').length || $('.f_manager-wrapper .file-grid__wrapper').length) && (!$('.f_manager-wrapper tbody, .f_manager-wrapper .file-grid__wrapper').parents('.modal').length)) {

					makeDirectoryDroppable();

					$('.f_manager-wrapper tbody').sortable({
						axis: "y",
						sort: function( event, ui ) {
							$('#preview').remove();
						}
					});

					$('.f_manager-wrapper .file-grid__wrapper .filepicker-item').draggable({
						revert: true,
						zIndex: 100,
						start: function( event, ui ) {
							ui.helper.css({
								'transform': 'scale(0.7)',
								'background-color': 'var(--ee-accent-light)'
							});
						},
						stop: function( event, ui ) {
							ui.helper.css({
								'transform': 'none',
								'background-color': 'transparent'
							});
						}
					});
				}
			}
		});
	}

	// Submitting bulk action sends the parent form
	$('body').on('click', 'button[name="bulk_action_submit"]:not([data-conditional-modal])', function(event) {
		//if the bulk action for is modal, but the selected action is not modal
		event.preventDefault();
		$('body').off('submit', form_selector);
		$(form_selector).submit();
	});

	// Submitting the search form
	$('body').on('submit', form_selector, function(event) {

		event.preventDefault();

		var url = typeof($(this).data('search-url'))!='undefined' ? $(this).data('search-url') : $(this).attr('action');
		url = url.replace(/(filter_by_keyword=).*?(&)/,'$1' + $('input[name="filter_by_keyword"]').val() + '$2');

		searchEntries('POST', url)

	});

	// Typing into the search form
	$('body').on('keyup', 'input[name="filter_by_keyword"]', function() {
		var val = $(this).val();
		clearTimeout(searchingTimeout)
		searchingTimeout = setTimeout(function() {
			//only submit when search is empty or min. 3 chars
			if (val.length == 0 || val.length >= 3 || !isNaN(val)) {
				var url = typeof($(form_selector).data('search-url'))!='undefined' ? $(form_selector).data('search-url') : $(form_selector).attr('action');
				url = url.replace(/(filter_by_keyword=).*?(&)/,'$1' + val + '$2');

				searchEntries('POST', url)

				searchingTimeout = null
			}

		}, 1000)
	});

	//changind the search scope
	$('body').on('change', 'input[name="search_in"]', function() {

		if ($('input[name="filter_by_keyword"]').val()!='') {
			searchEntries('POST');
		}
	
	});

	// Selecting a channel filter
	$('body').on('click', 'form .filter-search-bar .dropdown a.dropdown__link, form .filter-search-bar .filter-clear, form .filter-search-bar .filter__viewtype .filter-bar__button, form .filter-bar .dropdown a.dropdown__link, .filter-bar .filter-bar__button--clear, .pagination li a, .column-sort', function(event) {

		var search = $('input[name="filter_by_keyword"]').serialize();

		searchEntries('GET', $(this).attr('href') + '&' + search)

		event.preventDefault();
	});

	$('body').on('click', '[data-filter-url]', function(event) {

		searchEntries('GET', $(this).data('filter-url'))

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

			if (typeof(EE.viewManager) == 'undefined' && _form.attr('data-save-default-url') !== 'undefined') {
				saveDefaultUrl = _form.attr('data-save-default-url');
			}
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

	// D&D for file manager if table is not empty
	function ddFileToNotEmptyTable() {
		// table view
		$('.f_manager-wrapper table tbody').on('dragover', function(e) {
			e.preventDefault()
			e.stopPropagation()
			if( $('.f_manager-wrapper table tbody').length ) {
				openDDBlock('.f_manager-wrapper table tbody');
			}
		})

		// grid view
		$('.f_manager-wrapper .file-grid__wrapper').on('dragover', function(e) {
			e.preventDefault()
			e.stopPropagation()
			if ($('.f_manager-wrapper .file-grid__wrapper').length) {
				openDDBlock('.f_manager-wrapper .file-grid__wrapper');
			}
		});

		$('.f_manager-wrapper .file-upload-widget .file-field__dropzone').on('dragleave', function(e) {
			e.preventDefault()
			e.stopPropagation()
			if( $('.f_manager-wrapper table tbody').length || $('.f_manager-wrapper .file-grid__wrapper').length) {
				closeDDBlock();
			}
		});
	}

	function openDDBlock(dropArea) {
		let paginationWrap = 0;
		if ($(dropArea).parent().next().hasClass('f_manager-action-part')) {
			paginationWrap = $(dropArea).parent().next().outerHeight();
		}
		let ddHeight = $(dropArea).outerHeight()

		if ($(dropArea).hasClass('file-grid__wrapper')) {
			ddHeight = ddHeight + paginationWrap + 20
		}
		$('.f_manager-wrapper .file-upload-widget').css({
			'height': ddHeight,
			'bottom': '20px'
		});
		$('.f_manager-wrapper .file-upload-widget').addClass('open-dd')
	}

	function closeDDBlock() {
		$('.f_manager-wrapper .file-upload-widget').removeClass('open-dd')
		$('.f_manager-wrapper .file-upload-widget').css({
			'height': 'auto'
		})
	}

	ddFileToNotEmptyTable();

	// Move files to subfolder
	function makeDirectoryDroppable() {
		let modal_rel = 'modal-confirm-move-file';
		let ajax_url = $('.f_manager-wrapper [name=bulk_action_submit]').attr('data-confirm-ajax');
		let timer;
		$('.f_manager-wrapper tbody .drop-target, .f_manager-wrapper .file-grid__wrapper .drop-target').droppable({
			accept: "table .app-listing__row, .file-grid__wrapper .filepicker-item",
			tolerance: "intersect",
			revert: true,
			drop: function(e, ui) {
				var el = ui.draggable;
				var subfolder = e.target;
				var file_id = el.attr('file_id');
				var file_name = el.find('input[type=checkbox]').attr('name');
				var checkboxInput = el.find('input[type=checkbox]').attr('data-confirm');
				var subfolder_file_id = $(subfolder).attr('file_upload_id');

				e.preventDefault();

				// First adjust the checklist
				var modalIs = '.' + modal_rel;
				var modal = $(modalIs+', [rel='+modal_rel+']')
				$(modalIs + " .checklist").html(''); // Reset it

				$(modalIs + " .checklist").append('<li>' + checkboxInput + '</li>');
				// Add hidden <input> elements
				$(modalIs + " .checklist li:last").append(
					$('<input/>').attr({
						type: 'hidden',
						name: file_name,
						value: file_id
					})
				);

				$(modalIs + " .checklist li:last").addClass('last');

				if (typeof ajax_url != 'undefined') {
					$.post(ajax_url, $(modalIs + " form").serialize(), function(data) {
						$(modalIs + " .ajax").html(data);
						window.selectedFolder = subfolder_file_id;
						Dropdown.renderFields();
					});
				}

				modal.trigger('modal:open')
				$("#preview").remove();
			},
			over: function(e, ui) {
				var subfolder = e.target;
				$(subfolder).css('backgroundColor', 'var(--ee-accent-light)');
			},
			out: function(e, ui) {
				window.selectedFolder = null
				$(e.target).removeAttr('style');
			},
			deactivate: function(e, ui) {
				clearTimeout(timer);
				window.selectedFolder = null
				$(e.target).removeAttr('style');
			}
		});
	}

	if ( $('.f_manager-wrapper tbody').length || $('.f_manager-wrapper .file-grid__wrapper').length) {
		makeDirectoryDroppable();
		$('.f_manager-wrapper tbody').sortable({
			axis: "y",
			sort: function( event, ui ) {
				$('#preview').remove();
			}
		});

		$('.f_manager-wrapper .file-grid__wrapper .filepicker-item').draggable({
			revert: true,
			zIndex: 100,
			start: function( event, ui ) {
				ui.helper.css({
					'transform': 'scale(0.7)',
					'background-color': 'var(--ee-accent-light)'
				});
			},
			stop: function( event, ui ) {
				ui.helper.css({
					'transform': 'none',
					'background-color': 'transparent'
				});
			}
		});
	}

	if ( $('.member_manager-wrapper tbody').length) {
		// deselect row when clicking on toolbar
		$('body').on('click', '.member_manager-wrapper tbody tr td.app-listing__cell .button-toolbar', function(e) {
			if ($(this).parents('tr').hasClass('selected')) {
				$(this).parents('tr').children('td:last-child').children('input[type=checkbox]').click();
			}
		});
	}
});
