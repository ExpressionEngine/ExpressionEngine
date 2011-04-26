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

	var backend_url,
		current_directory = 0,
		dir_files_structure,
		dir_paths,
		default_img_url = EE.PATH_CP_GBL_IMG+'default.png',
		file_manager_obj,
		spinner_url = EE.THEME_URL+'/images/publish_file_manager_loader.gif',
		files_per_table,
		thumbs_per_page,
		trigger_callback,
		all_dirs = {},
		
		display_type;

	/*
	 * Sets up the filebrowser - call this before anything else
	 *
	 * @todo make callbacks overridable ($.extend)
	 */
	$.ee_filebrowser = function() {
		files_per_table = 14;
		thumbs_per_page = 36;

		// Setup!
		$.ee_filebrowser.endpoint_request('setup', function(data) {
			dir_files_structure	= {};
			dir_paths = {};
			
			file_manager_obj = $(data.manager).appendTo(document.body);
			
			for (var d in data.directories) {
				if ( ! current_directory) {
					current_directory = d;
				}
				dir_files_structure[d] = '';
			}

			createBrowser();
			
			// Load the file uploader
			if (typeof $.ee_fileuploader != "undefined") {
				$.ee_fileuploader({
					type: 'fileuploader',
					open: function(file_uploader) {
						$.ee_fileuploader.set_directory_id($('#dir_choice').val());
					},
					close: function(file_uploader) {
						// Make sure the button bar is showing the correct items
						$('#file_uploader').removeClass('upload_step_2').addClass('upload_step_1');

						if ($('#fileChooser').size()) {
							// Reload the contents for the current directory
							$.ee_filebrowser.reload_directory($('#dir_choice').val());	
						}
					},
					trigger: '#fileChooser #upload_form input'
				});
			};
		});
	};

	// --------------------------------------------------------------------

	/*
	 * Generic function to make requests to the backend. Everything! is handled by the backend.
	 *
	 * Currently supported types:
	 *		setup				 - called automatically | returns manager html and all directories
	 *		diretory			 - returns directory name
	 *		directories			 - returns all directories
	 *		directory_contents	 - returns directory information and files ({url: '', id: '', files: {...}})
	 */
	$.ee_filebrowser.endpoint_request = function(type, data, callback) {
		if ( ! callback && $.isFunction(data)) {
			callback = data;
			data = {};
		}
		
		data = $.extend(data, {'action': type});
		
		$.getJSON(EE.BASE+'&'+EE.filebrowser.endpoint_url+'&'+$.param(data), callback);
	};

	// --------------------------------------------------------------------
	
	/**
	 * Refreshes the file browser with the newly upload files
	 *
	 * @param {Number} directory_id The directory ID to refresh
	 */
	$.ee_filebrowser.reload_directory = function(directory_id) {
		$.ee_filebrowser.endpoint_request(
			'directory_contents',
			{"directory": directory_id},
			function(data) {
				all_dirs[directory_id] = data;
				
				// If you're looking at the same directory, rebuild the page
				if ($('#dir_choice').val() == directory_id) {
					build_pages(directory_id);
				};
			}
		);
	};
	
	// --------------------------------------------------------------------

	/*
	 * Allows you to bind elements that will open the file browser
	 * The callback is called with the file information when a file
	 * is chosen.
	 *
	 * @todo consider changing this to something event
	 *		 based so it doesn't force a click event.
	 */
	$.ee_filebrowser.add_trigger = function(el, field_name, callback) {
		if ( ! callback && $.isFunction(field_name)) {
			callback = field_name;
			field_name = 'userfile';
		}
		
		$(el).click(function() {
			var that = this;
			
			// Change the upload field to their preferred name
			$("#upload_file", file_manager_obj).attr('name', field_name);

			file_manager_obj.dialog("open");
			
			trigger_callback = function(file) {
				callback.call(that, file, field_name);
			};
			return false;
		});
	};
	
	// --------------------------------------------------------------------
	
	/*
	 * Place Image
	 *
	 * Convenience method that gets bound as an inline click event. Yes,
	 * inline click event - eat me.
	 */
	$.ee_filebrowser.placeImage = function(dir, img) {
		$.ee_filebrowser.clean_up(dir_files_structure[dir][img], '');
		return false;
	};

	// --------------------------------------------------------------------

	/*
	 * Clean Up
	 *
	 * Takes care of restoring the file upload, closing the modal, and firing needed callbacks
	 */
	$.ee_filebrowser.clean_up = function(file, original_upload_html) {
		// Clean up
		$("#page_0 .items").html(original_upload_html); // Restore the upload form
		file_manager_obj.dialog("close"); // close dialog
		trigger_callback(file);
	};

	// --------------------------------------------------------------------

	/*
	 * Builds the horizontal navigation.
	 * Only fills in thumbnails for the first page, all others are loaded when they come into view
	 */
	function build_pages(directory, offset) {
		if (isNaN(offset)) {
			offset = 0;
		}
				
		if (isNaN(directory)) {
			all_dirs[directory.id] = directory;
		} else if (typeof all_dirs[directory] == "undefined") {
			// In the event that all_dirs doesn't contain what we need, fire
			// off a request to endpoint_request to get us what we need
			return $.ee_filebrowser.endpoint_request('directory_contents', {'directory': directory}, function(data) {
				all_dirs[directory] = data;
				build_pages(directory, offset);
			});
		} else {
			directory = all_dirs[directory];
		}
		
		if ( ! directory in dir_files_structure) {
			return;
		}
		
		// Cache directory information
		dir_files_structure[directory.id] = directory.files;
		dir_paths[directory.id] = directory.url;
		
		$.each(directory.files, function(i, el) {
			el['img_id'] = i+'';
			el['directory'] = directory.id+'';
			el['is_image'] = ! (el.mime_type.indexOf("image") < 0);
		});
		
		// Clear everything
		var table_view = $("#tableView").detach(),
			viewSelectors = $("#viewSelectors").detach();
			
		table_view.find('tbody').empty();
		$('#file_chooser_body').empty().append(table_view);
		$("#file_chooser_footer").empty().append(viewSelectors);
		
		var per_page = (display_type == 'list') ? files_per_table : thumbs_per_page,
			pagination = {};

		offset = offset * per_page;
				
		if (display_type != 'list') {
			var images = build_image_list(directory),
				workon = directory.images.slice(offset, offset + per_page);
			
			$("#tableView").hide();

			$.tmpl("thumb", workon).appendTo("#file_chooser_body");
			
			// Add a last class to the 7th thumbnail
			$('a.file_chooser_thumbnail:nth-child(9n+2)').addClass('first');
			$('a.file_chooser_thumbnail:nth-child(9n+1)').addClass('last');
			$('a.file_chooser_thumbnail:gt(26)').addClass('last_row');
			
			// Change pagination for thumbnails
			pagination.pages_total = images.length;
		}
		else {
			var workon = directory.files.slice(offset, offset+per_page);

			$("#tableView").show();
			$.tmpl("fileRow", workon).appendTo("#tableView tbody");
		}
		
		build_footer(directory, offset, per_page, pagination);
	}
	
	$.ee_filebrowser.setPage = build_pages;

	// ------------------------------------------------------------------------ 
	
	/**
	 * Build the footer for the file chooser
	 *
	 * @param {Object} directory The directory object from build_pages
	 * @param {Number} offset The offset of files from build_pages
	 * @param {Number} per_page The number of files to show per page
	 * @param {Object} pagination The pagination object (if declared) from build_pages
	 */
	function build_footer(directory, offset, per_page, pagination) {
		var	total_files = (pagination.pages_total) ? pagination.pages_total : directory.files.length,
			pages = [];
		
		for (var i = 0, page_count = Math.ceil(total_files / per_page); i < page_count; i++) {
			pages[i] = i + 1;
		}

		$.extend(pagination, {
			'directory': directory.id,
			'pages_total': total_files,
			'pages_from': offset + 1, // Bump up offset by one because of zero indexed arrays
			'pages_to': (offset + per_page > total_files) ? total_files : offset + per_page,
			'pages_current': Math.floor(offset / per_page) + 1,
			'pages': pages
		});

		$.tmpl("pagination", pagination).appendTo("#file_chooser_footer")
			// Create an event handler for changes to the dropdown
			.find('#view_type')
				.val(display_type) // Make sure the dropdown is using the right value
				.change(function() {
					// Add class to file chooser body
					$('#file_chooser_body').removeClass('list thumb').addClass(this.value);
					
					display_type = this.value;
					build_pages($('#dir_choice').val());
				})
			.end()
			.find('select[name=category]')
				.replaceWith(directory.categories);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Build a list of images by looking through the directory files and seeing if thumbs exist
	 *
	 * @param {Object} directory The directory object from build_pages
	 */
	function build_image_list (directory) {
		if (typeof directory.images == "undefined") { 
			var images = [], 
				count = 0;

			for (var i = 0, max = directory.files.length; i < max; i++) {
				if (directory.files[i].is_image) {
					images.push(directory.files[i]);
				}
			}

			// Set images for posterity's sake
			directory.images = images;
			all_dirs[directory.id].images = images;
		};
		
		return directory.images;
	}

	// ------------------------------------------------------------------------ 

	/* 
	 * Dynamically loads files from a directory if it hasn't been loaded yet
	 */
	function loadFiles(directory) {
		if (dir_files_structure[directory] == "") {
			$.ee_filebrowser.endpoint_request('directory_contents', {'directory': directory}, build_pages);
		}
	}

	// --------------------------------------------------------------------

	/* 
	 * Sets up all filebrowser events
	 */
	function createBrowser() {
		
		// Set up modal dialog
		file_manager_obj.dialog({
			width: 968,
			height: 610,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: EE.filebrowser.window_title,
			autoOpen: false,
			zIndex: 99999,
			open: function(event, ui) {
				var current_directory = $('#dir_choice').val();
				
				// Are there files that need to be retrieved for this dir?
				// loadFiles will intelligently take care of minimizing HTTP requests
				loadFiles(current_directory);
			}
		});
		
		display_type = 'list';

		$('#dir_choice').change(function() {
			loadFiles(this.value);
			build_pages(this.value, 0);
		});
		
		$.template("fileRow", $('<tbody />').append($('#rowTmpl').remove().attr('id', '')));
		$.template("noFilesRow", $('#noFilesRowTmpl').remove());
		$.template("pagination", $('#paginationTmpl').remove());
		
		$.template("thumb", $('#thumbTmpl').remove());
		// $.template("NoFilesThumb", $('#rowTmpl'));
		
		// Bind the upload submit event
		$("#upload_form", file_manager_obj).submit($.ee_filebrowser.upload_start);
		
		// Add the display type as a class to file_chooser_body
		$('#file_chooser_body', file_manager_obj).addClass(display_type);
	}

})(jQuery);
