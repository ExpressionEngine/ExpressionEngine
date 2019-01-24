/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

EE.cp.export_email = {

	buttons: $('button[name="export"]'),
	site: 'all',

	init: function() {
		var that = this;

		EE.cp.export_email._init();
	},

	_init: function() {
		this._bindButton();
	},

	/**
	 * Bind the Reindex button to fire off the AJAX request and do the
	 * DOM manipulations necessary
	 */
	_bindButton: function() {
		var that = this;

		this.buttons.on('click', function(event) {
			event.preventDefault();
			button = $(event.target);

			button.addClass('work');
			button.text(button.data('work-text'));

			that._disableButton(true);
			that._sendAjaxRequest(0);
		});
	},

	/**
	 * Disables the Reindex button either to a working state or an error state
	 *
	 * @param	boolean	work	Whether or not to put the button in a working state
	 */
	_disableButton: function(work) {
		this.buttons.attr('disabled', true)

		if (work) {
			this.buttons.addClass('work')
			this.buttons.val(this.buttons.data('work-text'));
		} else {
			this.buttons.addClass('disable')
		}
	},

	/**
	 * Re-enables a button after it has been disabled
	 */
	_enableButton: function() {
		this.buttons.attr('disabled', false)
			.removeClass('work')
			.removeClass('disable')
	},

	/**
	 * Handles the network requests to the endpoint
	 *
	 * @param	integer	progress		Offset at which to continue
	 */
	_sendAjaxRequest: function(progress) {

		var request = new XMLHttpRequest(),
			that = this,
			data = {
				progress: 0,
				validate_email: $('input[name="validate_email"]').val()
			};

		if (progress !== undefined) {
			data = {
				progress: progress,
				validate_email: $('input[name="validate_email"]').val()
			};
		}

		// Make a query string of the JSON POST data
		data = Object.keys(data).map(function(key) {
			return encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
		}).join('&');

		request.open('POST', EE.export_email.endpoint, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.setRequestHeader('X-CSRF-TOKEN', EE.CSRF_TOKEN);

		request.onload = function() {
			try {
				var response = JSON.parse(request.responseText);
			} catch(e) {
				that._presentError(e);
				return;
			}

			if (request.status >= 200 && request.status < 400) {

				if (response.status == undefined) {
					that._presentError(response);
					return;
				}

				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				// Finished? Redirect to success screen
				if (response.status == 'finished') {
					that._updateProgress(100);
					window.location = EE.export_email.base_url;
					return;
				}

				// Keep CP session alive for large backups by faking mousemoveevents
				var event = document.createEvent('HTMLEvents');
				event.initEvent('mousemove', true, false);
				document.dispatchEvent(event);

				// Still more to do, update progress and kick off another AJAX request
				that._updateProgress(that._getPercentageForResponse(response));
				that._sendAjaxRequest(response.progress);
			} else {
				if (response.status == 'error') {
					that._presentError(response.message);
					return;
				}

				that._presentError(response);
			}
		};

		request.onerror = function() {
			that._presentError(response);
		};

		request.send(data);
	},

	/**
	 * Gets overall percentage that has been completed
	 *
	 * @param	object	response	Parsed JSON response from AJAX request to endpoint
	 */
	_getPercentageForResponse: function(response) {
		var progress = 0,
			total_members = EE.export_email.total_members;

		progress = Math.round(parseInt(response.progress) / total_members * 100);

		return progress > 100 ? 100 : progress;
	},

	/**
	 * Updates the progress bar UI to a set percentage
	 *
	 * @param	integer	percentage	Whole number (eg. 68) percentage
	 */
	_updateProgress: function(percentage) {
		var $progress = $('.progress-bar'),
			$progress_bar = $('.progress', $progress);

		if ($progress.is(':not(:visible)')) {
			$progress.show();
		};

		$progress_bar.css('width', percentage+'%');
	},


	/**
	 * Presents our inline error alert with a custom message
	 *
	 * @param	string	text	Error message
	 */
	_presentError: function(text) {
		var alert = EE.export_email.ajax_fail_banner.replace('%body%', text),
			alert_div = document.createElement('div'),
			form = document.querySelectorAll('form')[0];

		alert_div.innerHTML = alert;

		form.insertBefore(alert_div, form.firstChild);

		this._enableButton();
		this._disableButton();
	}
}

if (document.readyState != 'loading') {
	EE.cp.export_email.init();
} else {
	document.addEventListener('DOMContentLoaded', EE.cp.export_email.init);
}

$(document).ready(function () {
	$('.form-standard form').off('submit');
	$('button[type="submit"]').on('click', function(e) {
		$(e.target).hide();
	});
});
