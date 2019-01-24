/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

EE.file_manager = EE.file_manager || {};
EE.file_manager.sync_files = EE.file_manager.sync_files || {};

EE.file_manager.sync_db = 0;
EE.file_manager.sync_running = 0;
EE.file_manager.sync_errors = [];
EE.file_manager.resize_ids = [];
EE.file_manager.sync_timeout_id = 0;


$(document).ready(function() {
	EE.file_manager.sync_listen();
});

EE.file_manager.sync_listen = function() {
	$('.form-standard form input.btn').click(function(event) {
		event.preventDefault();

		// Get array of files
		EE.file_manager.sync_files = _.toArray(EE.file_manager.sync_files);

		// Get upload directory
		var upload_directory_id = EE.file_manager.sync_id;

		EE.file_manager.update_progress(0);

		// Disable sync button
		$('input.btn', this).prop('disabled', true);

		// Remove any existing alerts
		$('.app-notice--inline').remove();

		// Send ajax requests
		// Note- testing didn't show async made much improvement on time
		EE.file_manager.sync_timeout_id = setTimeout(function() {
			EE.file_manager.sync(upload_directory_id);
		}, 15);
	});
};

EE.file_manager.resize_ids = function() {
	var resize_ids = [];

	$('input[name="sizes[]"]').each(function() {
		var field = $(this);

		if (field.attr('type') == 'hidden' || // Hidden input from React
            (field.attr('type') == 'checkbox' && field.prop('checked') == true)) // Real checkbox
		{
			resize_ids.push($(this).val());
		}
	});

	return resize_ids;
};

/**
 * Fire off the Ajax request, which then listens for the finish and then fires off the next Ajax request and so on
 *
 * @param {Number} upload_directory_id The id of the upload directory to pass to the controller method
 */
EE.file_manager.sync = function(upload_directory_id) {

	// If no files are left, check if db sync has run- if so, get outta here
	if (EE.file_manager.sync_files.length <= 0) {
		if (EE.file_manager.db_sync == 'y') {
			clearTimeout(EE.file_manager.sync_timeout_id);
			return;
		}

		EE.file_manager.db_sync = 'y';
	};

	// There should only be one place we're splicing the files array and THIS is it
	var files_to_sync = EE.file_manager.sync_files.splice(0, 5);

	$.ajax({
		url: EE.file_manager.sync_endpoint,
		type: 'POST',
		dataType: 'json',
		data: {
			"XID": EE.XID,
			"upload_directory_id": upload_directory_id,
			"sizes": EE.file_manager.sync_sizes,
			"files": files_to_sync,
			"resize_ids" : EE.file_manager.resize_ids(),
			"db_sync": EE.file_manager.db_sync,
			"errors": EE.file_manager.sync_errors
		},
		beforeSend: function(xhr, settings) {
			// Increment the running count
			EE.file_manager.sync_running += 1;
		},
		complete: function(xhr, textStatus) {
			// Decrement the running count
			EE.file_manager.sync_running -= 1;

			// Update the progress bar
			var total_count       = EE.file_manager.sync_file_count,
				current_count     = EE.file_manager.sync_files.length,
				already_processed = total_count - current_count;

			EE.file_manager.update_progress(Math.round(already_processed / total_count * 100));

			// Fire off another Ajax request
			EE.file_manager.sync(upload_directory_id);

			EE.file_manager.finish_sync(upload_directory_id);
		},
		success: function(data, textStatus, xhr) {
			if (data.message_type != "success") {
				if (typeof(data.errors) != "undefined") {
					for (var key in data.errors) {
						EE.file_manager.sync_errors.push("<b>" + key + "</b>: " + data.errors[key]);
					}
				} else {
					EE.file_manager.sync_errors.push("<b>Undefined errors</b>"); d
				}
			}
		}
	});
};

/**
 * Show the sync complete summary
 *
 * This should contain the number of files processed, the number of errors and the errors themselves
 */
EE.file_manager.finish_sync = function(upload_directory_id) {
	if (EE.file_manager.sync_running == 0) {
		if (EE.file_manager.sync_errors.length == 0) {
			// No errors? Success flashdata message should be set,
			// redirect back to the sync page to show success message
			window.location = EE.file_manager.sync_baseurl;
		} else {
			// If there are errors, pass them through POST, there may be too
			// many to store in a flashdata cookie
			var input = $('<input>', { type: 'hidden', name: 'errors', value: JSON.stringify(EE.file_manager.sync_errors) });
			$('.wrap .form-standard form').append(input).submit();
		}
	};
};

/**
 * Update the progress bar
 *
 * @param {Number} progress_percentage The percentage of progress, represented as an integer (e.g. 56 = 56%)
 */
EE.file_manager.update_progress = function(progress_percentage) {

	var $progress = $('.progress-bar'),
		$progress_bar = $('.progress', $progress);

	if ($progress.is(':not(:visible)')) {
		$progress.show();
	};

	$progress_bar.css('width', progress_percentage+'%');
};
