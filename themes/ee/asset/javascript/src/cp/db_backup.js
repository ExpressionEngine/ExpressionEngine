/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

EE.cp.DbBackup = {

	buttons: $('.form-btns input.btn:visible'),

	init: function() {
		EE.cp.DbBackup._init();
	},

	_init: function() {
		this._bindButton();
	},

	/**
	 * Bind the Backup Database button to fire off the AJAX request and do the
	 * DOM manipulations necessary
	 */
	_bindButton: function() {
		var that = this;

		this.buttons.on('click', function(event) {
			event.preventDefault();
			that._disableButton(true);
			that._sendAjaxRequest();
		});
	},

	/**
	 * Disables the Backup Database button either to a working state or an error state
	 *
	 * @param	boolean	work	Whether or not to put the button in a working state
	 */
	_disableButton: function(work) {
		this.buttons.attr('disabled', true)

		if (work) {
			this.buttons.addClass('work')
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
	 * Handles the network requests to the backup endpoint
	 *
	 * @param	string	table_name	Table name at which to continue the backup,
	 *   if left blank, backup will be started from the beginning
	 * @param	integer	offset		Offset at which to continue the backup
	 * @param	string	file_path	The file path starting from the system
	 *   folder to store the backup
	 */
	_sendAjaxRequest: function(table_name, offset, file_path) {

		var data = {},
			request = new XMLHttpRequest(),
			that = this;

		if (table_name !== undefined) {
			data = {
				table_name: table_name,
				offset: offset,
				file_path: file_path
			};
		}

		// Make a query string of the JSON POST data
		data = Object.keys(data).map(function(key) {
			return encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
		}).join('&');

		request.open('POST', EE.db_backup.endpoint, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.setRequestHeader('X-CSRF-TOKEN', EE.CSRF_TOKEN);

		request.onload = function() {
			if (request.responseText.indexOf('bytes exhausted') != -1) {
				that._presentError(EE.db_backup.out_of_memory_lang);
				return;
			}

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
					window.location = EE.db_backup.base_url;
					return;
				}

				// Keep CP session alive for large backups by faking mousemoveevents
				var event = document.createEvent('HTMLEvents');
				event.initEvent('mousemove', true, false);
				document.dispatchEvent(event);

				// Still more to do, update progress and kick off another AJAX request
				that._updateProgress(that._getPercentageForResponse(response));
				that._sendAjaxRequest(response.table_name, response.offset, response.file_path);
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
	 * Gets overall percentage of backup that has been completed
	 *
	 * @param	object	response	Parsed JSON response from AJAX request to
	 *   backup endpoint
	 */
	_getPercentageForResponse: function(response) {
		var progress = 0,
			table_counts = EE.db_backup.table_counts;

		for (var key in table_counts) {
			if (table_counts.hasOwnProperty(key)) {
				if (key == response.table_name) {
					progress += parseInt(response.offset);
					break;
				} else {
					progress += parseInt(table_counts[key]);
				}
			}
		}

		progress = Math.round(progress / EE.db_backup.total_rows * 100);

		return progress > 100 ? 100 : progress;
	},

	/**
	 * Updates the progress bar UI to a set percentage
	 *
	 * @param	integer	percentage	Whole number (eg. 68) percentage
	 */
	_updateProgress: function(percentage) {
		var progress_bar = document.querySelectorAll('.progress')[0];

		progress_bar.style.width = percentage+'%';
	},

	/**
	 * Presents our inline error alert with a custom message
	 *
	 * @param	string	text	Error message
	 */
	_presentError: function(text) {
		var alert = EE.db_backup.backup_ajax_fail_banner.replace('%body%', text),
			alert_div = document.createElement('div');

		alert_div.innerHTML = alert;
		$('.form-standard .form-btns-top').after(alert_div);

		this._enableButton();
		this._disableButton();
	}
}


if (document.readyState != 'loading') {
	EE.cp.DbBackup.init();
} else {
	document.addEventListener('DOMContentLoaded', EE.cp.DbBackup.init);
}
