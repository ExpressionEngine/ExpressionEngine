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

		files_per_table = 10;
		thumbs_per_page = 21;

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
	 * Change Dimensions
	 *
	 * This function is responsible for auto-adding pixel values if the user
	 * chooses to maintain aspect ratio when resizing an image
	 */
	$.ee_filebrowser.change_dim = function(image, el) {
		var ratio;

		// If the constrain box isn't checked, leave everything alone
		if ($("#cloned #constrain:checked").length == 0)
		{
			return;
		}

		if (el.attr('id') == 'resize_width')
		{
			ratio = image.height/image.width;
			$("#resize_height").val(Math.floor(ratio * el.val()));
		}
		else
		{
			ratio = image.width/image.height;
			$("#resize_width").val(Math.floor(ratio * el.val()));
		}
	};

	// --------------------------------------------------------------------

	/*
	 * Submit Image Edit
	 *
	 * Submits the image edit form via AJAX and then runs cleanup on resulting information
	 */
	$.ee_filebrowser.submit_image_edit = function(file, original_upload_html) {
		$.ajax({
			type: "POST",
			url: EE.BASE+"&"+EE.filebrowser.endpoint_url+"&action=edit_image",
			data: $("#image_edit_form").serialize(),
			success: function(file_name) {
				file.file_name = file_name;
				file.dimensions = 'width="'+file.width+'" height="'+file.height+'" ';
				$.ee_filebrowser.clean_up(file, original_upload_html);
			},
			error: function(msg) {
				if ($.ee_notice)
				{
					$.ee_notice(msg.responseText, {"type" : "error"});
				}
				else
				{
					// strip html from error
					msg.responseText = msg.responseText.replace(/<p>/, "");
					alert(msg.responseText.replace(/<\/p>/, ""));
				}
			}
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
	 * Callback actions
	 */
	var callbacks = {
		upload_start: function() {
			$('input[name=upload_dir]').val($('#dir_choice').val());
			// $("#progress", file_manager_obj).show();
		},

		upload_success: function(file) {
			
			$.ee_filebrowser.clean_up(file, '');
			return;
			
			// change the contents of dir_files_structure to a blank string so that
			// next time that directory is viewed it will be re-polled for contents
			dir_files_structure[file.directory] = "";
			$("#page_"+file.directory+" .items", file_manager_obj).empty();

			// Hide!
			$("#progress", file_manager_obj).hide();

			// page_0 is the upload form. Save the original HTML so we can restore it for the next use
			var original_upload_html = $("#page_0 .items").html();

			// if this is an image, we need to offer editing options
			if (file.is_image)
			{
				// Here we over write it to offer options for the user to edit the image or return to publish
				$("#page_0 .items").html("<button id=\"resize_image\"><span>"+EE.lang.resize_image+"</span></button> "+EE.lang.or+" <button class=\"place_image\"><span>"+EE.lang.return_to_publish+"</span></button>").fadeIn("fast");

				// Place Image is essentially the same as "cancel", it'll just insert the file reference
				$(".place_image").click(function() {
					$.ee_filebrowser.clean_up(file, original_upload_html);
				});

				$("#resize_image").click(function() {
					// Let's draw the resize options into a form
					$("#page_0 .items").html($(".image_edit_form_options").clone().css("display", "block").attr('id', 'cloned'));

					$("#resize_width").val(file.width);
					$("#resize_height").val(file.height);

					$("#file").val(file.url_path);

					$("#resize_width, #resize_height").keyup(function() {
						$.ee_filebrowser.change_dim(file, $(this));
					});

					// Place Image is essentially the same as "cancel", it'll just insert the file reference
					$(".place_image").click(function(){
						$.ee_filebrowser.clean_up(file, original_upload_html);
					});

					// Rotation calculation
					$(".icons li").click(function() {
						var rotate_operation = $(this).attr("class");

						switch(rotate_operation)
						{
							case 'rotate_90r':
								rotate = 90;
								break;
							case 'rotate_90l':
								rotate = 270;
								break;
							case 'rotate_180':
								rotate = 180;
								break;
							case 'rotate_flip_vert':
								rotate = "vrt";
								break;
							case 'rotate_flip_hor':
								rotate = "hor";
								break;
							default:
							  rotate = "none";
						}

						// Clear all user entered values. If they are clicking a button, we don't
						// want those to take precedence
						$("#image_edit_form input:text").val("");

						// Stick the rotational code into the form so we can serialize and send
						$("#image_edit_form").prepend('<input type="hidden" name="rotate" value="'+rotate+'"/>');

						// @todo: make rotate icons appear "highlighted" to indicate choice

						$.ee_filebrowser.submit_image_edit(file, original_upload_html);
					});

					$("#image_edit_form").submit(function(){
						// if no options are filled out, then this is the equivalent of choosing not to edit the image
						if ($("#crop_width").val() == "" && $("#crop_height").val() == "" && $("#crop_x").val() == "" && 
							$("#crop_y").val() == "" && $("#resize_width").val() == "" && $("#resize_height").val() == "")
						{
							$.ee_filebrowser.clean_up(file, original_upload_html);
						}
						else
						{
							// This might be a clone, so pass the new height/width just to be sure
							file.width = $('#resize_width').val();
							file.height = $('#resize_height').val();
							// submit form data in background
							$.ee_filebrowser.submit_image_edit(file, original_upload_html);
						}

						return false; // kill form from leaving page
					});

				});
			}
			else
			{
				// Not an image file, close up the dialog and insert the file reference into the field
				$.ee_filebrowser.clean_up(file, original_upload_html);
			}
		},
		
		upload_error: function(error) {
			$("#progress", file_manager_obj).hide();
			if ($.ee_notice)
			{
				$.ee_notice(error.error, {"type": "error"});
			}
			else
			{
				// strip html from error
				error.error = error.error.replace(/<p>/, "");
				alert(error.error.replace(/<\/p>/, ""));
			}
			console.log(error);
		}
	};

	// --------------------------------------------------------------------

	// Add callbacks to filebrowser object
	$.ee_filebrowser = $.extend($.ee_filebrowser, callbacks);

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
		
		console.log(directory);
		// Cache directory information
		dir_files_structure[directory.id] = directory.files;
		dir_paths[directory.id] = directory.url;
		
		$.each(directory.files, function(i, el) {
			el['img_id'] = i+'';
			el['directory'] = directory.id+'';
			el['is_image'] = ! (el.mime_type.indexOf("image") < 0);
			if (el['is_image']) {
				el['thumb'] = directory.url + "/_thumbs/thumb_" + el.file_name;
			}
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
			$('a.file_chooser_thumbnail:nth-child(7n+2)').addClass('first');
			$('a.file_chooser_thumbnail:nth-child(7n+1)').addClass('last');
			
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
				if (directory.files[i].thumb) {
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
			width: 730,
			height: 495,
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
	}

})(jQuery);
