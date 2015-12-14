/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
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
		$('table .toolbar .settings a').click(function (e) {
			var settings_button = this;
			var modal = $('.' + $(this).attr('rel'));

			$.ajax({
				type: "GET",
				url: EE.template_settings_url.replace('###', $(this).data('template-id')),
				dataType: 'html',
				success: function (data) {
					loadSettingsModal(modal, data);
				}
			})
		});

		function loadSettingsModal(modal, data) {
			$('div.box', modal).html(data);

			// Bind validation
			EE.cp.formValidation.init(modal);

			$('form', modal).on('submit', function() {
				$.ajax({
					type: 'POST',
					url: this.action,
					data: $(this).serialize()+'&save_modal=yes',
					dataType: 'json',

					success: function(result) {
						if (result.messageType == 'success') {
							modal.trigger('modal:close');
						} else {
							loadSettingsModal(modal, result.body);
						}
					}
				});

				return false;
			});
		};

		// Reorder template groups
		EE.cp.folderList.bind('template-group', function(list) {
			// Create an array of template group names
			var template_groups = $.map($('> li', list), function(list_item) {
				return $(list_item).data('group_name');
			});

			$.ajax({
				url: EE.templage_groups_reorder_url,
				data: {'groups': template_groups },
				type: 'POST',
				dataType: 'json'
			});
		});
	});
})(jQuery);
