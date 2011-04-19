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
	
	var file_uploader,
		settings;

	/**
	 * Loads in the html needed and fires off the function to build the dialog
	 */
	$.ee_fileuploader = function(options) {
		var default_options = {};
		settings = $.extend({}, default_options, options);
		
		$.ee_filebrowser.endpoint_request('setup_upload', function(data) {
			file_uploader = $(data.uploader).appendTo(document.body);
			
			// Hide the Choose File button
			file_uploader.removeClass('upload_step_2').addClass('upload_step_1');
			
			$(document).ready(function() {
				$.ee_fileuploader.build_dialog();
			});
			
			if (typeof settings.load == 'function') {
				settings.load.call(this, file_uploader);
			};
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
				if (typeof settings.open == 'function') {
					settings.open.call(this, file_uploader);
				}
				
				upload_listen();
			},
			close: function() {
				if (typeof settings.close == 'function') {
					var file = window.upload_iframe.file;
					settings.close.call(this, file_uploader, file);
				};
			}
		});
		
		// Bind the open event to the specified trigger
		$(settings.trigger).live('click', function(event) {
			event.preventDefault();
			file_uploader.dialog('open');
		});
	};
	
	// --------------------------------------------------------------------
	
	/**
	 * Listen for clicks on the button_bar's upload file button
	 */
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the directory ID of the iframe
	 *
	 * @param {Number} directory_id The directory ID
	 * @returns Directory ID if it's a valid directory ID, false otherwise
	 * @type Number|Boolean
	 */
	$.ee_fileuploader.set_directory_id = function(directory_id) {
		// Is this a number?
		if ( ! isNaN(parseInt(directory_id, 10))) {
			var source = file_uploader.find('iframe').attr('src'),
				source_position = source.search('&directory_id=');

			// Check to see if the source already has directory_id and remove it
			if (source_position > 0) {
				source = source.substring(0, source_position);
			};

			file_uploader.find('iframe').attr('src', source + '&directory_id=' + directory_id);
			
			return directory_id;
		};
		
		return false;
	};
	
})(jQuery);
