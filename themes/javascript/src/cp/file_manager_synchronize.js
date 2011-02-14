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

$('synchronize').click(function(event) {
	event.preventDefault();
	
	// Hide button
	$(this).hide();
	
	// Show progress bar
	EE.file_manager.update_progress();
	
	// Get array of files
	EE.file_manager.sync_files.files = jsFiles;
	EE.file_manager.sync_files.file_count = jsFiles.length;
	
	// Get upload directory
	var upload_directory_id = $('#upload_directory_id');
	
	// Send first few ajax requests
	for (var i = 0; i < 2; i++) {
		setTimeout(function() {
			EE.file_manager.sync_files(upload_directory_id);
		}, 15);
	};
});

EE.file_manager.sync_files.running = 0;
EE.file_manager.sync_files.errors = [];

/**
 * Fire off the Ajax request, which then listens for the finish and then fires off the next Ajax request and so on
 *
 * @param {Number} upload_directory_id The id of the upload directory to pass to the controller method
 */
EE.file_manager.sync_files = function(upload_directory_id) {
	if (EE.file_manager.sync_files.files.length <= 0) {
		return EE.filemanager.finish_sync();
	};
	
	// There should only be one place we're splicing the files array and THIS is it
	var files_to_sync = EE.file_manager.sync_files.files.splice(0, 5);

	$.ajax({
		url: EE.BASE + '&C=content_files&M=sync_directory',
		type: 'POST',
		dataType: 'json',
		data: {
			"XID": EE.XID,
			"upload_directory": upload_directory,
			"files": files_to_sync
		},
		beforeSend: function(xhr, settings) {
			// Increment the running count
			EE.file_manager.sync_files.running += 1;
		},
		complete: function(xhr, textStatus) {
			// Decrement the running count
			EE.file_manager.sync_files.running -= 1;
			
			// Fire off another Ajax request
			EE.file_manager.sync_files(upload_directory_id);
			
			// Update the progress bar
			var total_count = EE.file_manager.sync_files.file_count,
				current_count = EE.file_manager.sync_files.files.length,
				already_processed = total_count - current_count;
			
			EE.file_manager.update_progress(Math.round(already_processed / total_count));
		},
		success: function(data, textStatus, xhr) {
			// I doubt I'll need this...
		},
		error: function(xhr, textStatus, errorThrown){
			// If the errorThrown is not an array, make it so
			if ( ! $.isArray(errorThrown)) {
				errorThrown = [errorThrown];
			};
			
			for (var i = 0, max = errorThrown.length; i < max; i++) {
				EE.file_manager.sync_files.errors.push(errorThrown[i]);
			};
		}
	});
};

/**
 * Show the sync complete summary
 *
 * This should contain the number of files processed, the number of errors and the errors themselves
 */
EE.file_manager.finish_sync = function() {
	if (EE.file_manager.sync_files.running == 0) {
		$('.progress').hide();
		
		var sync_complete = {
			'files_processed': EE.file_manager.sync_files.file_count - EE.file_manager.sync_files.errors.length,
			'errors': EE.file_manager.sync_files.errors,
			'error_count': EE.file_manager.sync_files.errors.length
		};
		
		$.tmpl('sync_complete_template', sync_complete).appendTo();
	};
};

/**
 * Update the progress bar
 * 
 * @param {Number} progress_percentage The percentage of progress, represented as an integer (e.g. 56 = 56%)
 */
EE.file_manager.update_progress = function(progress_percentage) {
	var $progress = $('#progress');
	
	if ($progress.is(':not(:visible)')) {
		$progress.show();
	};
	
	$progress.progressbar({
		value: progress_percentage
	});
};