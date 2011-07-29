/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

EE.file_manager = EE.file_manager || {};
EE.file_manager.sync_files = EE.file_manager.sync_files || {};

EE.file_manager.sync_db = 0;
EE.file_manager.sync_running = 0;
EE.file_manager.sync_errors = [];
EE.file_manager.resize_ids = [];


$(document).ready(function() {
	$.template("sync_complete_template", $('#sync_complete_template'));
	
	$('table#dimensions').toggle_all();
	EE.file_manager.sync_listen();
});

EE.file_manager.sync_listen = function() {
	$('.tableSubmit input').click(function(event) {
		event.preventDefault();

		// Hide button
		$(this).hide();

		// Show progress bar
		EE.file_manager.update_progress();

		// Get array of files
		EE.file_manager.sync_files = _.toArray(EE.file_manager.sync_files);

		// Get upload directory
		var upload_directory_id = _.keys(EE.file_manager.sync_sizes)[0];
		
		EE.file_manager.update_progress(0);

		// Send first few ajax requests
		for (var i = 0; i < 2; i++) {
			setTimeout(function() {
				EE.file_manager.sync(upload_directory_id);
			}, 15);
		};
	});
};

EE.file_manager.resize_ids = function() {
	var resize_ids = [];

	$('input[name="toggle[]"]:checked').each(function() {
		resize_ids.push($(this).val());
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
			return;
		}
		
		EE.file_manager.db_sync = 'y';
	};
	
	// There should only be one place we're splicing the files array and THIS is it
	var files_to_sync = EE.file_manager.sync_files.splice(0, 5);

	$.ajax({
		url: EE.BASE + '&C=content_files&M=do_sync_files',
		type: 'POST',
		dataType: 'json',
		data: {
			"XID": EE.XID,
			"upload_directory_id": upload_directory_id,
			"sizes": EE.file_manager.sync_sizes,
			"files": files_to_sync,
			"resize_ids" : EE.file_manager.resize_ids(),
			"db_sync": EE.file_manager.db_sync
		},
		beforeSend: function(xhr, settings) {
			// Increment the running count
			EE.file_manager.sync_running += 1;
		},
		complete: function(xhr, textStatus) {
			// Decrement the running count
			EE.file_manager.sync_running -= 1;
			
			// Fire off another Ajax request
			EE.file_manager.sync(upload_directory_id);
			
			// Update the progress bar
			var total_count       = EE.file_manager.sync_file_count,
				current_count     = EE.file_manager.sync_files.length,
				already_processed = total_count - current_count;
			
			EE.file_manager.update_progress(Math.round(already_processed / total_count * 100));
			EE.file_manager.finish_sync(upload_directory_id);


		},
		success: function(data, textStatus, xhr) {
			if (data.message_type == "failure") {

				for (var key in data.errors) {
					EE.file_manager.sync_errors.push("<b>" + key + "</b>: " + data.errors[key]);
				}
			}
		}
	});
};

EE.file_manager.get_directory_name = function(upload_directory_id) {
	return $('#sync table:first tr[data-id=' + upload_directory_id + '] td:first').text();	
};

/**
 * Show the sync complete summary
 *
 * This should contain the number of files processed, the number of errors and the errors themselves
 */
EE.file_manager.finish_sync = function(upload_directory_id) {
	if (EE.file_manager.sync_running == 0) {
		$('#progress').hide();
		
		var sync_complete = {
			'directory_name':  EE.file_manager.get_directory_name(upload_directory_id),
			'files_processed': EE.file_manager.sync_file_count - EE.file_manager.sync_errors.length,
			'errors':          EE.file_manager.sync_errors,
			'error_count':     EE.file_manager.sync_errors.length
		};
	
		$.tmpl('sync_complete_template', sync_complete).appendTo($('#sync'));

        // You can't have a conditional template in a table because Firefox ignores anything in a table that's untablelike
        if (sync_complete.error_count == 0) {
            $('#sync_complete ul').hide();
        } else {
			$('#sync_complete span').hide();
		}
	};
};

/**
 * Update the progress bar
 * 
 * @param {Number} progress_percentage The percentage of progress, represented as an integer (e.g. 56 = 56%)
 */
EE.file_manager.update_progress = function(progress_percentage) {
	var $progress = $('#progress'),
		$progress_bar = $('#progress_bar');
	
	if ($progress.is(':not(:visible)')) {
		$progress.show();
	};
	
	$progress_bar.progressbar({
		value: progress_percentage
	});
};
