/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function($) {

"use strict";

var Updater = {

	init: function() {
		this._overlay = $('.update-overlay');
		this._success_overlay = $('.update-success-overlay');
		this._issue_overlay = $('.update-issue-overlay');
		this._bindUpdateButton($('p.update-btn a.submit'));
	},

	_bindUpdateButton: function(button) {
		var that = this;

		button.on('click', function(event) {
			event.preventDefault();

			that._presentOverlay();
		});
	},

	_presentOverlay: function() {
		this._overlay.addClass('update-open');
		$('.update-status1').fadeIn(100);

		this._requestUpdate();
	},

	_requestUpdate: function(step) {
		var that = this,
			action = EE.BASE + '&C=updater';

		if (step !== undefined) {
			action += '&step='+step;
		}

		$.ajax({
			type: 'POST',
			url: action,
			dataType: 'json',
			success: function(result) {
				if (result.messageType == 'success') {
					that._updateStatus(result.message);
					if (result.nextStep !== undefined && result.nextStep !== false) {
						that._requestUpdate(result.nextStep);
					}
				}
				if (result.messageType == 'error') {
					that._showError(result.message);
				}
			},
			error: function(data) {
				that._showError(data.message);
			}
		});
	},

	_updateStatus: function(message) {
		var process_container = $('.update-process', this._overlay),
			current_message = $('p:visible', process_container),
			next_message = $('p:hidden', process_container);

		next_message.html(message);

		current_message.fadeOut(100);
		next_message.fadeIn(100);
	},

	_showError: function(message) {
		this._overlay.removeClass('update-open');

		$('p', this._issue_overlay).first().html(message);

		this._issue_overlay.addClass('update-open');
	}
}

})(jQuery);
