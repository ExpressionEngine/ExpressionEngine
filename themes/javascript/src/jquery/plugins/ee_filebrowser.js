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
		settings = {},
		current_field = '',
		display_type;

	/*
	 * Sets up the filebrowser - call this before anything else
	 *
	 * @todo make callbacks overridable ($.extend)
	 */
	$.ee_filebrowser = function() {
		files_per_table = 5;
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
					type: 'filebrowser',
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
	 * @param {String} el The jQuery Object or selector
	 * @param {String} field_name The name of the field you're adding a trigger to
	 * @param {Object} new_settings The settings for this specific field,
	 *		the only settings used are content_type and directory. content_type
	 *		can be set to 'any' or 'image'. Directory can be set to 'all' or
	 *		a specific directory ID
	 */
	$.ee_filebrowser.add_trigger = function(el, field_name, new_settings, callback) {
		if (! callback) {
			if ($.isFunction(field_name)) {
				callback = field_name;
				field_name = 'userfile';
				settings[field_name] = {content_type: 'any', directory: 'all'};
			}
			else if ($.isFunction(new_settings)) {
				callback = new_settings;
				settings[field_name] = {content_type: 'any', directory: 'all'};
			}
		} else {
			settings[field_name] = new_settings;
		}
		
		$(el).click(function() {
			var that = this;

			// Change the upload field to their preferred name
			current_field = field_name;

			// Restrict the upload directory options to the specified directory
			hide_directories();
			
			// Rebuild pages since each upload directory can have different settings
			build_pages($('#dir_choice').val());

			file_manager_obj.dialog("open");
			
			trigger_callback = function(file) {
				callback.call(that, file, field_name);
			};
			return false;
		});
	};
	
	// --------------------------------------------------------------------
	
	/**
	 * Gets the settings of the currently selected field
	 *
	 * @returns An object containing the settings passed in for the current field
	 * @type Object
	 */
	$.ee_filebrowser.get_current_settings = function() {
		return settings[current_field];
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
		
		var images,
			workon;
			
		if (display_type != 'list') {
			images = build_image_list(directory);
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
			if (settings[current_field].content_type == "image") {
				images = build_image_list(directory);
				workon = directory.images.slice(offset, offset + per_page);
				pagination.pages_total = images.length;
			} else {
				workon = directory.files.slice(offset, offset + per_page);
			};

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
		
		var $pagination_dropdown = $('<select />', {
			"id": "current_page",
			"name": "current_page"
		});
		
		for (var i = 0, max = pages.length; i < max; i++) {
			$pagination_dropdown.append($('<option />', {
				"value": i,
				"text": "Page " + (i + 1)
			}));
		};
		
		$.extend(pagination, {
			'directory': directory.id,
			'pages_total': total_files,
			'pages_from': offset + 1, // Bump up offset by one because of zero indexed arrays
			'pages_to': (offset + per_page > total_files) ? total_files : offset + per_page,
			'pages_current': Math.floor(offset / per_page) + 1,
			'pages': pages,
			'dropdown': $pagination_dropdown.wrap('<div />').parent().html(),
			'previous': EE.filebrowser.previous,
			'next': EE.filebrowser.next,
			'pagination_needed': (pages.length > 1) ? true : false
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
				.replaceWith(directory.categories)
			.end()
			// Create a listener for the pagination dropdown
			.find('select[name=current_page]')
				.val(pagination.pages_current - 1)
				.change(function() {
					build_pages($('#dir_choice').val(), $(this).val());
					show_next_previous(pagination.pages.length);
				})
			.end()
			// Create a listener for the previous link
			.find('a.previous')
				.click(function(event) {
					event.preventDefault();
					change_page(-1);
					show_next_previous(pagination.pages.length);
				})
			.end()
			// Create a listener for the next link
			.find('a.next')
				.click(function(event) {
					event.preventDefault();
					change_page(1);
					show_next_previous(pagination.pages.length);
				})
			.end();
	}
	
	// ------------------------------------------------------------------------ 
	
	/**
	 * Change the page for the next/previous pagination, takes a modifier (either
	 * 1 or -1) depending if we're going forward or backward, changes the dropdown
	 * and rebuilds the pages
	 *
	 * @param {Number} modifier Either +1 if going to the next page or -1 if 
	 *		going to the previous page
	 */
	function change_page (modifier) {
		if (typeof modifier == "undefined") {
			modifier = 0;
		};
		
		var current_page = $('#current_page').val(),
			new_page = parseInt(current_page, 10) + modifier;
		
		$('#current_page').val(new_page);
		build_pages($('#dir_choice').val(), new_page);
	}
	
	// ------------------------------------------------------------------------ 
	
	/**
	 * Shows the next or previous links depending on what page we're looking at
	 * and how many pages there are
	 *
	 * @param {Number} total_pages The total number of pages
	 */
	function show_next_previous (total_pages) {
		$('#file_chooser_footer #paginationLinks a').removeClass('visualEscapism');
		
		if ($('#current_page').val() == 0) {
			$('#file_chooser_footer #paginationLinks .previous').addClass('visualEscapism');
		} else if ($('#current_page').val() == (total_pages - 1)) {
			$('#file_chooser_footer #paginationLinks .next').addClass('visualEscapism');
		};
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
			height: 615,
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
		
		// Bind the upload submit event
		$("#upload_form", file_manager_obj).submit($.ee_filebrowser.upload_start);
		
		// Add the display type as a class to file_chooser_body
		$('#file_chooser_body', file_manager_obj).addClass(display_type);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Hides the directory switcher based on settings passed to add_trigger
	 */
	function hide_directories() {
		if (settings[current_field].directory != 'all') {
			$('#dir_choice', file_manager_obj).val(settings[current_field].directory);
			$('#dir_choice_form', file_manager_obj).hide();
		} else {
			$('#dir_choice', file_manager_obj).val();
			$('#dir_choice_form', file_manager_obj).show();
		};
	}

})(jQuery);
