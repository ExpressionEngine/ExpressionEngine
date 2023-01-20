/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

	var file_uploader,
		original_upload_html,
		settings,
		current_file,
		delete_file = true;

	/**
	 * Loads in the html needed and fires off the function to build the dialog
	 *
	 * Options you can pass in:
	 *	- type:			string		either 'filebrowser' or 'filemanager', this is
	 *								used to determine what buttons to show
	 *	- trigger:		string		the jQuery selector to bind the upload dialog to
	 *	- load:			function	callback called when the modal is loaded
	 *	- open:			function	callback called when opening the modal
	 *	- after_upload:	function	callback called after the upload is complete
	 *	- close:		function	callback called when closing the modal
	 */
	$.ee_fileuploader = function(options) {
		var default_options = {};
		settings = $.extend({}, default_options, options);

		$.ee_filebrowser.endpoint_request('setup_upload', function(data) {
			file_uploader = $(data.uploader);
			$(document.body).append(file_uploader);
			_EE_uploader_attached();
		});
	};


	$.ee_fileuploader.setSource = function(id, url) {
		file_uploader.find(id).attr('src', url);
		file_uploader = file_uploader.first();

		// Hide the Choose File button
		file_uploader.removeClass().addClass('before_upload');

		// Remove unneeded buttons
		if (settings.type == "filemanager") {
			file_uploader.find('.button_bar .filebrowser').remove();
		} else if (settings.type == "filebrowser") {
			file_uploader.find('.button_bar .filemanager').remove();
		}

		$(document).ready(function() {
			$.ee_fileuploader.build_dialog();
		});

		// Call load callback
		if (typeof settings.load == 'function') {
			settings.load.call(this, file_uploader);
		}
	}

	/**
	 * Builds the jQuery UI dialog, adds two listeners to the dialog, and adds
	 * a listener to the upload button on the file chooser
	 */
	$.ee_fileuploader.build_dialog = function() {
		file_uploader.dialog({
			width: 600,
			height: 370,
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

				// Reset current_file
				current_file = {};

				// Hide loading animation
				$('#file_uploader .button_bar .loading').addClass('visualEscapism');

				// Disable upload file button
				$.ee_fileuploader.reset_upload();

				// Save original contents for reset on close
				if (original_upload_html === undefined) {
					original_upload_html = file_uploader.html();
				}
				// Call open callback
				if (typeof settings.open == 'function') {
					settings.open.call(this, file_uploader);
				}

				upload_listen();
			},
			close: function() {
				if (typeof window.upload_iframe.file != "undefined") {
					if (delete_file) {
						// Delete the file
						$.ajax({
							url: EE.BASE+'&'+EE.fileuploader.delete_url,
							type: 'POST',
							dataType: 'json',
							data: {
								"file": current_file.file_id,
								"XID": EE.XID
							},
							error: function(xhr, textStatus, errorThrown){
								console.log(textStatus);
							}
						});
					}

					// Call close callback, passing the file info
					if (typeof settings.close == 'function') {
						settings.close.call(this, file_uploader, current_file);
					}
				}

				file_uploader.html(original_upload_html);
			}
		});

		// Bind the open event to the specified trigger
		$(document).on('click', settings.trigger, function(event) {
			event.preventDefault();
			file_uploader.dialog('open');
		});
	};

	/**
	 * Listen for clicks on the button_bar's upload file button
	 */
	var upload_listen = function() {
		$('#file_uploader .button_bar #rename_file').click(function(event) {
			event.preventDefault();
			$('#file_uploader iframe').contents().find('form').trigger('submit');
		});

		$('#file_uploader .button_bar .cancel').live('click', function(event) {
			event.preventDefault();

			$iframe = $('#file_uploader iframe').contents();

			// If we're editing file metadata, clear out content
			if ($iframe.find('#edit_file_metadata').length) {
				// Change both resize dimensions back to default
				$iframe.find('#resize input').each(function(index) {
					$(this).val($(this).data('default')).removeClass('oversized');
				});

				// Clear the radio buttons
				$iframe.find("#rotate input").prop('checked', false);
			}
			// Otherwise close the dialog
			else {
				file_uploader.dialog('close');
			}
		});
	};

	/**
	 * Disable the upload by changing the button bar
	 *
	 * @param {Boolean} disable Whether or not to disable the button/upload
	 */
	$.ee_fileuploader.reset_upload = function(disable) {
		if (typeof disable == "undefined") {
			disable = true;
		}

		// Hide loading indicator
		$('#file_uploader .button_bar .loading').addClass('visualEscapism');

		// Disable the upload file button
		if (disable === true) {
			$('#file_uploader .button_bar #upload_file')
				.addClass('disabled-btn')
				.removeClass('submit')
				.unbind();
		}
	};

	/**
	 * Fired by the index of the upload after the file field has been
	 * filled out
	 */
	$.ee_fileuploader.enable_upload = function() {
		$('#file_uploader .button_bar #upload_file')
			.addClass('submit')
			.removeClass('disabled-btn')
			.click(function(event) {
				event.preventDefault();
				$('#file_uploader .button_bar .loading').removeClass('visualEscapism');
				$('#file_uploader iframe').contents().find('form').trigger('submit');
			});
	};

	/**
	 * Cleans up the file upload and the file chooser after a file has
	 * been selected
	 *
	 * @param {Object} file File object passed from
	 */
	var clean_up = function() {
		// Hide the dialog
		file_uploader.dialog('close');

		// Close filebrowser
		$.ee_filebrowser.clean_up(current_file);
	};

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
				source_position = source.search('&directory_id='),
				field_settings = $.ee_filebrowser.get_current_settings();

			// Check to see if the source already has directory_id and remove it
			if (source_position > 0) {
				source = source.substring(0, source_position);
			}

			source = source + '&directory_id=' + directory_id;

			// Add restrict_directory get variable if we need to restrict to a directory
			if ($('.dir_choice_container:visible').length <= 0) {
				source = source + '&restrict_directory=true';
			}

			// Add restrict_image get variable if we need to restrict to images
			if (field_settings && field_settings.content_type == "image") {
				source = source + '&restrict_image=true';
			}

			file_uploader.find('iframe').attr('src', source);

			return directory_id;
		}

		return false;
	};

	/**
	 * This method is called if the file already exists, comes before upload
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
	$.ee_fileuploader.file_exists = function(file) {
		$.ee_fileuploader.update_file(file);

		change_class('file_exists');
	};

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
		$.ee_fileuploader.update_file(file);

		// Make sure the file doesn't get deleted if the window is closed
		delete_file = false;

		// Call after upload callback
		if (typeof settings.after_upload == "function") {
			settings.after_upload.call(this, file_uploader, current_file);
		}

		// Change the step to step 2
		change_class('after_upload');

		// Show/Hide "Edit Image" link based on whether or not it's an image
		$('#edit_image').toggle(file.is_image);

		if (settings.type == "filemanager") {
			// Create listener for the browse_files button
			$('#file_uploader .button_bar').on('click', '#browse_files', function(event) {
				clean_up();
				event.preventDefault();
			});

			// Create listeners for the edit_file and edit_image links (not buttons)
			var pages = ['edit_file', 'edit_image'];

			for (var i = 0, size = pages.length; i < size; i++) {
				var edit_url = $('.mainTable tr.new:first td:has(img) a[href*='+pages[i]+']').attr('href');
				$('#'+pages[i], '#file_uploader .button_bar').attr('href', edit_url);
			}
		} else if (settings.type == "filebrowser") {
			// Create listener for the choose_file button
			$('#file_uploader .button_bar').on('click', '#choose_file', function(event) {
				clean_up();
				event.preventDefault();
			});

			// Create listener for edit file button
			$('#file_uploader .button_bar').on('click', '#edit_file_modal', function(event) {
				$('#file_uploader iframe').contents().find('form#edit_file').trigger('submit');
				change_class('edit_modal');
				event.preventDefault();
			});

			// Create listener for the save file button (independent of choose file)
			$('#file_uploader .button_bar').on('click', '#save_file', function(event) {
				$('#file_uploader iframe').contents().find('form#edit_file_metadata').trigger('submit');
				event.preventDefault();
			});
		}
	};

	/**
	 * Helper method to change the current file since we can't rely on
	 * window.iframe.variable to always get the latest variable...
	 *
	 * @param {Object} file Object representing the just uploaded file
	 */
	$.ee_fileuploader.update_file = function(file) {
		current_file = file;
	};

	/**
	 * Helper method to change the class of the modal
	 *
	 * @param {String} class_name Name of the class that should be on the modal
	 */
	var change_class = function (class_name) {
		$('#file_uploader')
			.removeClass('before_upload after_upload file_exists edit_modal')
			.addClass(class_name);
	};
})(jQuery);
