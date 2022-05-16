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
			if ( $(this).parents('form').find('.f_manager-action-part .bulk-action-bar').length > 0) {
				if ($('.file-grid__wrapper').find('input:checked').length == 0 ) {
					$(this).parents('form').find('.f_manager-action-part .bulk-action-bar').addClass('hidden');
				} else {
					$(this).parents('form').find('.f_manager-action-part .bulk-action-bar').removeClass('hidden');
				}
			}
		});

		new MutableSelectField('files_field', EE.fileManager.fileDirectory);

		$('.thumbnail_img').hover(function(e) {
			var X = e.offsetX;
			var Y = e.offsetY;
			var top = Y + 20 + 'px';
			var left = X + 20 + 'px';
			if ($(this).siblings('.tooltip-img').length) {
				$(this).parents('td').css({position: 'relative'});
				$(this).siblings('.tooltip-img').css({
					display: 'block',
					top: top,
					left: left,
				});
			}
		}, function(){
			$(this).siblings('.tooltip-img').css({display: "none"})
		});
	});
})(jQuery);
