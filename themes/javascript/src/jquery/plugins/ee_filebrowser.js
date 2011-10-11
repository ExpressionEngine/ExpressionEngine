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

Array.max = function(array) {
    return Math.max.apply(Math, array);
};

Array.min = function(array) {
    return Math.min.apply(Math, array);
};

(function($) {

	var file_manager_obj,
		current_field = '',
		display_type = 'list',
		files_per_table = 15,
		thumbs_per_page = 36,
		backend_url,
		
		current_directory = 0,
		dir_info = {},
		dir_files = {},
		settings = {},
		
		trigger_callback;

	/*
	 * Sets up the filebrowser - call this before anything else
	 *
	 * @todo make callbacks overridable ($.extend)
	 */
	$.ee_filebrowser = function() {
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
		if ( typeof callback == 'undefined' && $.isFunction(data)) {
			callback = data;
			data = {};
		}
		
		data = $.extend(data, {'action': type});
		
		$.ajax({
			url: EE.BASE + '&' + EE.filebrowser.endpoint_url,
			type: 'GET',
			dataType: 'json',
			data: data,
			cache: false,
			success: function(data, textStatus, xhr) {
				if (typeof callback == 'function') {
					callback.call(this, data);
				};
			}
		});
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
			build_page($('#dir_choice').val());

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
	$.ee_filebrowser.placeImage = function(file_id) {
		$.ee_filebrowser.endpoint_request(
			'file_info',
			{"file_id": file_id},
			function(file) {
				$.ee_filebrowser.clean_up(file, '');
			}
		);
		
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
		
		// Clear caches
		dir_info = {};
		dir_files = {};
	};

	// --------------------------------------------------------------------
	
	/**
	 * Refreshes the file browser with the newly upload files
	 *
	 * @param {Number} directory_id The directory ID to refresh
	 */
	$.ee_filebrowser.reload_directory = function(directory_id) {
		// Force a refresh on the directory info and also rebuild the pages
		$.ee_filebrowser.directory_info(directory_id, true, function(data) {
			build_page(directory_id, 0);
		});
	};
	
	// ------------------------------------------------------------------------ 
	
	function build_page(directory_id, page_offset) {
		// Check if offset exists
		if (isNaN(page_offset)) {
			page_offset = 0;
		}
		
		var per_page	= (display_type == 'list') ? files_per_table : thumbs_per_page,
			offset		= page_offset * per_page,
			images_only	= (display_type == 'list' && settings[current_field].content_type != 'image') ? false : true;
		
		// Setup dir_files
		if (typeof dir_files[directory_id] == 'undefined') {
			dir_files[directory_id] = {};
		};
		
		if (typeof dir_files[directory_id][page_offset] == 'undefined') {
			$.ee_filebrowser.endpoint_request(
				'directory_contents',
				{
					'directory_id': directory_id,
					'limit': 		per_page,
					'offset': 		offset
				},
				function(data) {
					var files = data.files,
						cache_pages = 0,
						page_indexes = [];
					
					// Count the number of pages
					$.each(dir_files[directory_id], function(index, val) {
						page_indexes[cache_pages] = index;
						cache_pages = cache_pages + 1;
					});

					// Keep cache small-ish
					if (cache_pages > 3) {
						if (page_offset < Array.min(page_indexes)) {
							delete dir_files[directory_id][Array.max(page_indexes)];
						} else if (page_offset > Array.max(page_indexes)) {
							delete dir_files[directory_id][Array.min(page_indexes)];
						};
					};
					
					// Cache the file information
					if (typeof dir_files[directory_id][page_offset] == 'undefined') {
						dir_files[directory_id][page_offset] = files;
					};
					
					// Build pages
					build_page_from_template(
						dir_files[directory_id][page_offset],
						directory_id,
						offset,
						per_page,
						images_only
					);
				}
			);
		} else {
			build_page_from_template(
				dir_files[directory_id][page_offset],
				directory_id,
				offset,
				per_page,
				images_only
			);
		};
	}
	
	// --------------------------------------------------------------------

	/*
	 * Builds the horizontal navigation.
	 * Only fills in thumbnails for the first page, all others are loaded when they come into view
	 */
	function build_page_from_template(files, directory_id, offset, per_page, images_only) {
		var table_view = $("#tableView").detach(),
			viewSelectors = $("#viewSelectors").detach();
		
		// Clear everything
		table_view.find('tbody').empty();
		$('#file_chooser_body').empty().append(table_view);
		$("#file_chooser_footer").empty().append(viewSelectors);
		
		// Display the data
		if (display_type != 'list') {
			$("#tableView").hide();
			$.tmpl("thumb", files).appendTo("#file_chooser_body");
			
			// Add a last class to the 7th thumbnail
			$('a.file_chooser_thumbnail:nth-child(9n+2)').addClass('first');
			$('a.file_chooser_thumbnail:nth-child(9n+1)').addClass('last');
			$('a.file_chooser_thumbnail:gt(26)').addClass('last_row');
		}
		else {
			$("#tableView").show();
			$.tmpl("fileRow", files).appendTo("#tableView tbody");
		}

		// Build the pagination
		$.ee_filebrowser.directory_info(directory_id, false, function(data) {
			build_footer(directory_id, offset, per_page, images_only);
		});
	}
	
	// ------------------------------------------------------------------------ 
	
	/**
	 * Get's the directory's info for a particular directory
	 *
	 * @param {Number} directory_id ID of the directory you want a count for
	 * @param {Boolean} refresh Override to get the latest info from the db
	 */
	$.ee_filebrowser.directory_info = function(directory_id, refresh, callback) {
		if (typeof refresh == 'undefined') {
			refresh = false;
		};
		
		if (typeof dir_info[directory_id] == 'undefined' || refresh == true) {
			$.ee_filebrowser.endpoint_request(
				'directory_info',
				{"directory_id": directory_id},
				function(data) {
					dir_info[directory_id] = data;
					
					if (typeof callback == "function") {
						callback.call(this, data);
					};
				}
			);
		} else if (typeof callback == "function") {
			callback.call(this, dir_info[directory_id]);
		};
	};
	
	// ------------------------------------------------------------------------ 
	
	/**
	 * Build the footer for the file chooser
	 *
	 * @param {Object} directory The directory object from build_pages
	 * @param {Number} offset The offset of files from build_pages
	 * @param {Number} per_page The number of files to show per page
	 * @param {Boolean} images_only TRUE if only images, FALSE otherwise
	 */
	function build_footer(directory_id, offset, per_page, images_only) {
		// Set a default for images_only
		if (typeof images_only == "undefined") {
			images_only = false;
		};
		
		var total_files = dir_info[directory_id].file_count,
			page_count = Math.ceil(total_files / per_page);
		
		// Create the dropdown pagination
		var $pagination_dropdown = $('<select />', {
			"id": "current_page",
			"name": "current_page"
		});
		
		// Fill the pagination dropdown with page options
		for (var i = 0, max = page_count; i < max; i++) {
			$pagination_dropdown.append($('<option />', {
				"value": i,
				"text": "Page " + (i + 1)
			}));
		};
		
		// Figure out the information for the pagination block
		var pagination = {
			'pages_total': total_files,
			'pages_from': offset + 1, // Bump up offset by one because of zero indexed arrays
			'pages_to': (offset + per_page > total_files) ? total_files : offset + per_page,
			
			'pages_current': Math.floor(offset / per_page) + 1,
			
			'pagination_needed': (page_count > 1) ? true : false,
			'dropdown': $pagination_dropdown.wrap('<div />').parent().html(),
			
			'previous': EE.filebrowser.previous,
			'next': EE.filebrowser.next
		};
		
		// Remove existing pagination
		$('#paginationLinks, #pagination_meta').remove();
		
		$.tmpl("pagination", pagination).appendTo("#file_chooser_footer")
			// Create an event handler for changes to the dropdown
			.find('#view_type')
				.val(display_type) // Make sure the dropdown is using the right value
				.change(function() {
					// Add class to file chooser body
					$('#file_chooser_body').removeClass('list thumb').addClass(this.value);
					
					// Reset dir_files cache
					dir_files = {};
					
					// Change display type
					display_type = this.value;
					
					// Rebuild pages
					build_page($('#dir_choice').val());
				})
			.end()
			// Populate the categories dropdown
			// .find('select[name=category]')
			// 	.replaceWith(directory.categories)
			// .end()
			// Create a listener for the pagination dropdown
			.find('select[name=current_page]')
				.val(pagination.pages_current - 1)
				.change(function() {
					build_page($('#dir_choice').val(), $(this).val());
				})
			.end()
			// Create a listener for the previous link
			.find('a.previous')
				.click(function(event) {
					event.preventDefault();
					change_page(-1);
				})
			.end()
			// Create a listener for the next link
			.find('a.next')
				.click(function(event) {
					event.preventDefault();
					change_page(1);
				})
			.end();
			
		show_next_previous(page_count);
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
		build_page($('#dir_choice').val(), new_page);
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
			}
		});

		// Create listener for the dir choice
		$('#dir_choice').change(function() {
			build_page(this.value, 0);
		});
		
		// Get templates and remove code from view
		$.template("fileRow", $('<tbody />').append($('#rowTmpl').remove().attr('id', false)));
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
