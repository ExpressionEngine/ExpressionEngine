/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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

		function showBigImage(button) {
			var tooltip = button.find('#preview');
			var element = button.find('img.thumbnail_img');
			var placement = 'top-end';
			var offset = '60px, 25px';
			new Popper(element, tooltip, {
				placement: placement,
				modifiers: {
					offset: {
						enabled: true,
						offset: offset
					},
					preventOverflow: {
						boundariesElement: 'viewport',
					},
					flip: {
						behavior: ['right', 'left']
					}
				},
			});
		}

		$('body').on({
			mouseenter: function () {
				var path = $(this).data('url');
				var alt = $(this).attr('alt');
				var parent = $(this).parent();
				var width = $(this).find('img').width();
				var height = $(this).find('img').height();
				var style;

				if (width > height) {
					style = 'max-width: 200px';
				} else {
					style = 'max-height: 200px';
				}
				parent.prepend("<p id='preview'><img src='" + path + "' alt='"+alt+"' style='"+style+"' /></p>");
				showBigImage(parent);
			}, mouseleave: function () {
				$("#preview").remove();
			}
		}, '.f_manager-wrapper .imgpreview');
	});
})(jQuery);
