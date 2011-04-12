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

(function($) {
	
	var file_uploader;

	/**
	 * Loads in the html needed and fires off the function to build the dialog
	 */
	$.ee_fileuploader = function() {
		$.ee_filebrowser.endpoint_request('setup_upload', function(data) {
			file_uploader = $(data.uploader).appendTo(document.body);
			
			// Hide the Choose File button
			file_uploader.removeClass('upload_step_2').addClass('upload_step_1');
			
			$(document).ready(function() {
				$.ee_fileuploader.build_dialog();
			});
		});
	};
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the jQuery UI dialog, adds two listeners to the dialog, and adds
	 * a listener to the upload button on the file chooser
	 */	
	$.ee_fileuploader.build_dialog = function() {
		file_uploader.dialog({
			width: 600,
			height: 300,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: EE.fileuploader.window_title,
			autoOpen: false,
			zIndex: 99999,
			open: function() {
				var dir_id = $('#dir_choice').val(),
					source = file_uploader.find('iframe').attr('src'),
					source_position = source.search('&directory_id=');
					
				// Check to see if the source already has directory_id and remove it
				if (source_position > 0) {
					source = source.substring(0, source_position);
				};
				
				// Set a GET variable on the iframe to automatically select the correct directory
				file_uploader.find('iframe').attr('src', source + '&directory_id=' + dir_id);
				
				upload_listen();
			},
			close: function() {
				$.ee_filebrowser.reload_directory($('#dir_choice').val());
			}
		});
		
		// Listen for clicks on the filebrowser
		$('#fileChooser #upload_form input').live('click', function(event) {
			file_uploader.dialog('open');
		});
	};
	
	// --------------------------------------------------------------------
	
	var upload_listen = function() {
		$('#file_uploader .button_bar #upload_file').click(function(event) {
			event.preventDefault();
			
			$('#file_uploader iframe').contents().find('form').submit();
		});
	};
	
	// --------------------------------------------------------------------
	
	/**
	 * Cleans up the file upload and the file chooser after a file has
	 * been selected
	 *
	 * @param {Object} file File object passed from 
	 */
	var clean_up = function(file) {
		// Mark the step in the button bar
		$('#file_uploader').removeClass('upload_step_2').addClass('upload_step_1');
		
		// Hide the dialog
		file_uploader.dialog('close');

		// Close filebrowser
		$.ee_filebrowser.clean_up(file, '');
	};

	// --------------------------------------------------------------------

	/**
	 * Listener for the place file button on the button bar
	 *
	 * @param {Object} file File object passed from 
	 */
	$.ee_fileuploader.place_file = function(file) {
		// Change the step to step 2
		$('#file_uploader').removeClass('upload_step_1').addClass('upload_step_2');
		
		// Create listener for the place file button
		$('#file_uploader .button_bar #choose_file').click(function(event) {
			event.preventDefault();
			
			clean_up(file);
		});
	};
	
})(jQuery);
