/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

	var file_manager_obj,
		current_field = '',
		display_type = 'list',

		current_directory = 0,
		settings = {},
		error = '',

		$table = null,

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

						if ($('#file_browser').size()) {
							// Reload the contents for the current directory
							$.ee_filebrowser.reload();
						}
					},
					trigger: '#file_browser #upload_form input'
				});
			};
		});
	};

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
				if (data.error) {
					error = data.error;
					return;
				}

				if (typeof callback == 'function') {
					callback.call(this, data);
				};
			}
		});
	};

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
			// Check to see if we have any errors from setup
			if (error) {
				alert(error);
				return false;
			}

			var that = this;

			// Change the upload field to their preferred name
			current_field = field_name;

			// Restrict the upload directory options to the specified directory
			hide_directories();

			file_manager_obj.dialog("open");

			trigger_callback = function(file) {
				callback.call(that, file, field_name);
			};
			return false;
		});
	};

	/**
	 * Gets the settings of the currently selected field
	 *
	 * @returns An object containing the settings passed in for the current field
	 * @type Object
	 */
	$.ee_filebrowser.get_current_settings = function() {
		return settings[current_field];
	};

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
				trigger_callback(file);
				file_manager_obj.dialog("close"); // close dialog & clean up
			}
		);

		return false;
	};

	/**
	 * Clear caches and close the file browser
	 */
	$.ee_filebrowser.clean_up = function(file) {

		if (file_manager_obj == undefined) {
			return;
		}

		if (file) {
			trigger_callback(file);
		}

		// Clear out keyword filter
		$('#keywords', file_manager_obj).val('');

		file_manager_obj.dialog("close"); // clears caches
	};


	/**
	 * Refreshes the file browser with the newly upload files
	 *
	 * @param {Number} directory_id The directory ID to refresh
	 * @deprecated since 2.4, use reload()
	 */
	$.ee_filebrowser.reload_directory = function(directory_id) {
		$.ee_filebrowser.reload();
	};

	/**
	 * Refreshes the file browser with the newly upload files
	 */
	$.ee_filebrowser.reload = function() {
		if ($table) {
			$table.table('clear_cache');
			$table.table('refresh');
		};
	};

	/*
	 * Sets up all filebrowser events
	 */
	function createBrowser() {
		var $dir_choice = $('#dir_choice');

		// Make the file manager 95% as wide as the browser window,
		// but no more than 974px
		var file_manager_width = $(window).width() * 0.95;
		if (file_manager_width > 974)
		{
			file_manager_width = 974;
		}

		// Set up modal dialog
		file_manager_obj.dialog({
			width: file_manager_width,
			height: 615,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: EE.filebrowser.window_title,
			autoOpen: false,
			zIndex: 99999,
			open: function(event, ui) {
				var field_dir = settings[current_field].directory;

				if ( ! isNaN(field_dir)) {
					$dir_choice.val(field_dir);
				}

				// force a trigger check
				$dir_choice.trigger('interact');
				var current_directory = $('#dir_choice').val();
			},
			close: function(event, ui) {
				// Clear out keyword filter
				$('#keywords', file_manager_obj).val('');
			}
		});

		var $tables = $('#file_browser_body').find('table');

		$tables.each(function() {
			$table = $(this);

			if ($table.data('table_config')) {
				return false; //break
			}
		});

		var config = $table.data('table_config');
		$table.table(config);


		// Set directory in case filter happens before input has changed (because the
		// filter is only set on certain interaction events)
		// $table.table('add_filter', { 'dir_choice': $dir_choice.val() });

		$table.table('add_filter', $dir_choice);
		$table.table('add_filter', $('#keywords'));

		var table_template = $table.table('get_template');
			thumb_template = $('#thumbTmpl').remove().html(),
			table_container = $table.table('get_container'),
			thumb_container = $('#file_browser_body'); //$('div').insertBefore($table);

		$('#view_type').change(function() {
			if (this.value == 'thumb') {
				$table.detach();
				$table.table('set_container', thumb_container);
				$table.table('set_template', thumb_template);
				$table.table('add_filter', { 'per_page': 36 });
			} else {
				thumb_container.html($table);
				$table.table('set_container', table_container);
				$table.table('set_template', table_template);
				$table.table('add_filter', { 'per_page': 15 });
			}
		});

		// Bind the upload submit event
		$("#upload_form", file_manager_obj).submit($.ee_filebrowser.upload_start);

		// Add the display type as a class to file_browser_body
		$('#file_browser_body', file_manager_obj).addClass(display_type);
	}

	/**
	 * Hides the directory switcher based on settings passed to add_trigger
	 */
	function hide_directories() {
		if (settings[current_field].directory != 'all') {
			$('#dir_choice', file_manager_obj).val(settings[current_field].directory);
			$('#dir_choice_form .dir_choice_container', file_manager_obj).hide();
		} else {
			$('#dir_choice', file_manager_obj).val();
			$('#dir_choice_form .dir_choice_container', file_manager_obj).show();
		};
	}

})(jQuery);
