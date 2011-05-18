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
		settings,
		delete_file = true;

	/**
	 * Loads in the html needed and fires off the function to build the dialog
	 *
	 * Options you can pass in:
	 *	- type: 		string		either 'filebrowser' or 'filemanager', this is 
	 *								used to determine what buttons to show
	 *	- trigger: 		string		the jQuery selector to bind the upload dialog to
	 *	- load: 		function	callback called when the modal is loaded
	 *	- open: 		function	callback called when opening the modal
	 *	- after_upload: function	callback called after the upload is complete
	 *	- close: 		function	callback called when closing the modal
	 */
	$.ee_fileuploader = function(options) {
		var default_options = {};
		settings = $.extend({}, default_options, options);
		
		$.ee_filebrowser.endpoint_request('setup_upload', function(data) {
			file_uploader = $(data.uploader).appendTo(document.body);
			
			// Hide the Choose File button
			file_uploader.removeClass().addClass('before_upload');
			
			// Remove unneeded buttons
			if (settings.type == "filemanager") {
				file_uploader.find('.button_bar .filebrowser').remove();
			} else if (settings.type == "filebrowser") {
				file_uploader.find('.button_bar .filemanager').remove();
			};
			
			$(document).ready(function() {
				$.ee_fileuploader.build_dialog();
			});
			
			// Call load callback
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
				// Make sure we're on before_upload
				change_class('before_upload');

				
				// Call open callback
				if (typeof settings.open == 'function') {
					settings.open.call(this, file_uploader);
				}
				
				upload_listen();
			},
			close: function() {
				if (typeof window.upload_iframe.file != "undefined") {
					var file = window.upload_iframe.file;
					
					if (delete_file) {
						// Delete the file
						$.ajax({
							url: EE.BASE+'&'+EE.fileuploader.delete_url,
							type: 'POST',
							dataType: 'json',
							data: {
								"file": file.file_id,
								"XID": EE.XID
							},
							error: function(xhr, textStatus, errorThrown){
								console.log(textStatus);
							}
						});
					};
					
					// Call close callback, passing the file info
					if (typeof settings.close == 'function') {
						settings.close.call(this, file_uploader, file);
					};
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
		$('#upload_file, #rename_file', '#file_uploader .button_bar').click(function(event) {
			event.preventDefault();
			$('#file_uploader iframe').contents().find('form').submit();
		});
		
		$('#file_uploader .button_bar .cancel').live('click', function(event) {
			event.preventDefault();
			file_uploader.dialog('close');
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
	
	// --------------------------------------------------------------------
	
	/**
	 * This method is called if the file already exists, comes before upload
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
	$.ee_fileuploader.file_exists = function(file) {
		change_class('file_exists');
	};
	
	// --------------------------------------------------------------------
	
	/**
	 * This method is called after the upload
	 *
	 * Responsibilities
	 *	1. Call after_upload callback
	 *	2. Change the class to after_upload
	 *	3. Establish listeners for the buttons
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
	$.ee_fileuploader.after_upload = function(file) {
		// Make sure the file doesn't get deleted if the window is closed
		delete_file = false;
		
		// Call after upload callback
		if (typeof settings.after_upload == "function") {
			settings.after_upload.call(this, file_uploader, file);
		};
		
		// Change the step to step 2
		change_class('after_upload');
		
		// Create listener for the place file button
		if (settings.type == "filemanager") {
			if (file.is_image) {
				$('#file_uploader .button_bar #edit_file').show().click(function(event) {
					// Get edit action
					var edit_url = $('.mainTable tr.new:first td:has(img) a[href*=edit_image]').attr('href');
					$(this).attr('href', edit_url);
				});
			} else {
				// Hide the edit file button if it's not an image.
				$('#file_uploader .button_bar #edit_file').hide();
			};
		} else if (settings.type == "filebrowser") {
			$('#file_uploader .button_bar #choose_file').click(function(event) {
				event.preventDefault();
				clean_up(file);
			});
		};
	};
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Helper method to change the class of the modal
	 *
	 * @param {String} class_name Name of the class that should be on the modal
	 */	
	var change_class = function (class_name) {
		$('#file_uploader')
			.removeClass('before_upload after_upload file_exists')
			.addClass(class_name);
	};
})(jQuery);
