/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {
	$(document).ready(function () {
		$('a[rel=modal-view-file]').click(function (e) {
			var modal = $(this).attr('rel');
			$.ajax({
				type: "GET",
				url: EE.file_view_url.replace('###', $(this).data('file-id')),
				dataType: 'html',
				success: function (data) {
					$("." + modal + " div.box").html(data);
					SelectField.renderFields()
				}
			})
		});

		$('.f_manager-wrapper tbody, .f_manager-wrapper .file-grid__wrapper').sortable({
			cursor: "move"
		})

		// Select images if Grid view
		$('body').on('change', '.file-metadata__wrapper input:checkbox', function () {
			if ($(this).is(":checked")) {
				$(this).closest(".file-grid__file").addClass('selected');
			} else {
				$(this).closest(".file-grid__file").removeClass('selected');
			};
		});

		// If Selected All Files are checked
		$('body').on('change', ".file-grid__checkAll input[type=checkbox]", function(){
			if(this.checked){
				$(".file-metadata__wrapper input[type='checkbox']").each(function(){
					this.checked = true;
					$(this).parent().parent().parent().addClass('selected')
				});
			} else {
				$(".file-metadata__wrapper input[type='checkbox']").each(function(){
					this.checked=false;
					$(this).parent().parent().parent().removeClass('selected')
				});
			}
		});

		// if selected all files was checked and some of elements unchecked
		$('body').on('change', ".file-metadata__wrapper input[type='checkbox']", function () {
			if ($(this).is(":checked")) {
				var isAllChecked = 0;

				$(".file-metadata__wrapper input[type='checkbox']").each(function(){
					if(!this.checked) isAllChecked = 1;
				})

				if(isAllChecked == 0){
					$(".file-grid__checkAll input[type=checkbox]").prop("checked", true);
				}
			} else {
				$(".file-grid__checkAll input[type=checkbox]").prop("checked", false);
			}
		});

		// show/hide bulk-action-bar for the File Manager page Table view
		$('body').on('change', '.f_manager-wrapper table td:first-child input[type=checkbox], .f_manager-wrapper table th:first-child input[type=checkbox]', function() {
			if ($(this).parent('td').length) {
				if($(this).is(':checked')) {
					$(this).parents('tr').addClass('selected');
				} else {
					$(this).parents('tr').removeClass('selected');
				}
			}
			var allCheckboxesLength = $('.f_manager-wrapper table td:first-child input[type=checkbox]').length;
			var selectedCheckboxes = [];
			$('.f_manager-wrapper table td:first-child input[type=checkbox]:checked').each(function() {
				selectedCheckboxes.push($(this));
			});

			if(selectedCheckboxes.length == 0 || selectedCheckboxes.length == allCheckboxesLength) {
				$('.app-listing__header input[type=checkbox]').removeClass('intermediate');
			} else {
				$('.app-listing__header input[type=checkbox]').addClass('intermediate');
			}

			if (selectedCheckboxes.length >= 2) {
				$('.f_manager-action-part .bulk-action-bar select option').each(function(){
					var el = $(this)
					if($(this).val() == 'edit' || $(this).val() == 'copy_link' || $(this).val() == 'replace'){
						el.attr('disabled', 'disabled');
					}
				});
			} else {
				$('.f_manager-action-part .bulk-action-bar select option').each(function(){
					$(this).removeAttr('disabled');
				});
			}


			if ($(this).parents('form').find('.f_manager-action-part .bulk-action-bar').length > 0) {
				if ($(this).parents('table').find('input:checked').length == 0) {
					$(this).parents('.table-responsive').siblings('.f_manager-action-part').find('.bulk-action-bar').addClass('hidden');
				} else {
					$(this).parents('.table-responsive').siblings('.f_manager-action-part').find('.bulk-action-bar').removeClass('hidden');
				}
			}
		});

		// show/hide bulk-action-bar for the File Manager page Grid view
		$('body').on('change', '.f_manager-wrapper .file-grid__wrapper input[type=checkbox], .f_manager-wrapper .file-grid__checkAll input[type=checkbox]', function() {
			var allCheckboxesLength = $('.f_manager-wrapper .file-grid__wrapper input[type=checkbox]').length;
			var selectedCheckboxes = [];
			$('.f_manager-wrapper .file-grid__wrapper input[type=checkbox]:checked').each(function() {
				selectedCheckboxes.push($(this));
			});

			if(selectedCheckboxes.length == 0 || selectedCheckboxes.length == allCheckboxesLength) {
				$('.file-grid__checkAll input[type=checkbox]').removeClass('intermediate');
			} else {
				$('.file-grid__checkAll input[type=checkbox]').addClass('intermediate');
			}

			if (selectedCheckboxes.length >= 2) {
				$('.f_manager-action-part .bulk-action-bar select option').each(function(){
					var el = $(this)
					if($(this).val() == 'edit' || $(this).val() == 'copy_link' || $(this).val() == 'replace'){
						el.attr('disabled', 'disabled');
					}
				});
			} else {
				$('.f_manager-action-part .bulk-action-bar select option').each(function(){
					$(this).removeAttr('disabled');
				});
			}

			if ( $(this).parents('form').find('.f_manager-action-part .bulk-action-bar').length > 0) {
				if ($('.file-grid__wrapper').find('input:checked').length == 0 ) {
					$(this).parents('form').find('.f_manager-action-part .bulk-action-bar').addClass('hidden');
				} else {
					$(this).parents('form').find('.f_manager-action-part .bulk-action-bar').removeClass('hidden');
				}
			}
		});
		
		// new MutableSelectField('files_field', EE.fileManager.fileDirectory);

		$('body').on({
			mouseenter: function () {
				var path = $(this).data('url');
				var alt = $(this).attr('alt');
				var parent = $(this).parent();
				var top = $(this).offset().top;
				var left = $(this).offset().left;
				parent.append("<p id='preview'><img src='"+ path +"' alt='"+ alt +"' /></p>");
				$("#preview").css({
					'top': (top + 20) + "px",
					'left': (left - 200) + "px",
					'display': 'flex',
				}).fadeIn();
			}, mouseleave: function () {
				$("#preview").remove();
			}
		}, '.f_manager-wrapper .imgpreview');

		$('.main-nav__toolbar').on('click', 'a.dropdown__link', function(e){
			e.preventDefault();
			var path = $(this).data('path');
			var upload_location_id = $(this).data('upload_location_id');

			if (!path) {
				path = '';
			}

			$('.main-nav__toolbar .dropdown__scroll .f_open-filepicker').attr('data-upload_location_id', upload_location_id);
			$('.main-nav__toolbar .dropdown__scroll .f_open-filepicker').attr('data-path', path);

			$('.main-nav__toolbar .dropdown__scroll .f_open-filepicker').trigger('click');
		});
	});
})(jQuery);
