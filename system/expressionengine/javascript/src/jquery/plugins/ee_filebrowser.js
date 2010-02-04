/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/*!
 * ExpressionEngine Filebrowser Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

(function($) {

	var thumbs_per_page, dir_files_structure, dir_paths, backend_url, trigger_callback,
		current_directory = 0,
		spinner_url = EE.THEME_URL+'images/publish_file_manager_loader.gif',
		default_img_url = EE.PATH_CP_GBL_IMG+'default.png',
		file_manager_obj, cur_dir_seek;

	/*
	 * Sets up the filebrowser - call this before anything else
	 *
	 * @todo make callbacks overridable ($.extend)
	 */
	$.ee_filebrowser = function() {

		thumbs_per_page = 20;

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
	}

	// --------------------------------------------------------------------

	/*
	 * Generic function to make requests to the backend. Everything! is handled by the backend.
	 *
	 * Currently supported types:
	 *		setup				- called automatically | returns manager html and all directories
	 *		diretory			- returns directory name
	 *		directories			- returns all directories
	 *		directory_contents	- returns directory information and files ({url: '', id: '', files: {...}})
	 */
	$.ee_filebrowser.endpoint_request = function(type, data, callback) {
		if ( ! callback && $.isFunction(data)) {
			callback = data;
			data = {};
		}
		
		data = $.extend(data, {'action': type});
		$.getJSON(EE.BASE+'&'+EE.filebrowser.endpoint_url+'&'+$.param(data), callback);
	}

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
			// Change the upload field to their preferred name
			$("#upload_file", file_manager_obj).attr('name', field_name);

			file_manager_obj.dialog("open");

			trigger_callback = function(file) {
				callback.call(el, file, field_name);
			};
			return false;
		});
	}

	// --------------------------------------------------------------------

	/*
	 * Change Dimensions
	 *
	 * This function is responsible for auto-adding pixel values if the user
	 * chooses to maintain aspect ratio when resizing an image
	 */
	$.ee_filebrowser.change_dim = function(image, el) {

		// If the constrain box isn't checked, leave everything alone
		if ($("#cloned #constrain:checked").length == 0)
		{
			return;
		}

		if (el.attr('id') == 'resize_width')
		{
			var ratio = image.height/image.width;
			$("#resize_height").val(Math.floor(ratio * el.val()));
		}
		else
		{
			var ratio = image.width/image.height;
			$("#resize_width").val(Math.floor(ratio * el.val()));
		}
	}

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
			success: function(file_name){
				file.name = file_name;
				file.dimensions = 'width="'+file.width+'" height="'+file.height+'" ';
				$.ee_filebrowser.clean_up(file, original_upload_html);
			},
			error: function(msg){
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
	}

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
	}

	$.ee_filebrowser.reset = function() {
		$("#file_manager").scrollable({api: true}).begin();
	}

	// --------------------------------------------------------------------

	/*
	 * Callback actions
	 */
	var callbacks = {
		upload_start: function() {
			$("#progress", file_manager_obj).show();
		},

		upload_success: function(file) {
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
				$(".place_image").click(function(){
					$.ee_filebrowser.clean_up(file, original_upload_html);
				});

				$("#resize_image").click(function(){
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
	function build_pages(directory) {
		
		if ( ! directory.id in dir_files_structure) {
			return;
		}
		
		// Cache directory information
		dir_files_structure[directory.id] = directory.files;
		dir_paths[directory.id] = directory.url;
		
		// Time to pimp your file browser! (How lame am I? I actually found that funny when I first typed it. -Allard)
		var thumbs = "",
			item_count = 0,
			_pages = [],
			api = $("#page_"+directory.id, file_manager_obj).scrollable();

		// For performance, we compile the thumbnails into a string and append once rather then appending each image
		// We'll need to reload for every page, but it's efficient enough to make it worth it

		$.each(directory.files, function(j, file) {

			if (j % thumbs_per_page == 0 && j != 0) {
				_pages.push(thumbs);
				thumbs = '';
			}

			// non-images get a "default" icon
			if (file.mime == false || file.mime.indexOf("image") < 0) {
				thumbs += '<div><div title="{filedir_'+directory.id+'}|'+file.name+'"><img title="'+default_img_url+'" src="'+default_img_url+'" alt="default thumbnail" /></div>'+file.name+'</div>';
			}
			else {
				// generic image if no thumb exists	(@todo generic thumbnail thing?)
				// thumb only on first page
				// spinner for thumbs on subsequent pages

				thumbs += '<div><div title=\''+file.dimensions+'\'><img class="image" title="{filedir_'+directory.id+'}'+file.name+'" src="';

				if ( ! file.has_thumb) {

					thumbs += default_img_url

					// If this is a viewable page without thumbs, build it
					if (j < thumbs_per_page)
					{
						$.ajax({
							type: "POST",
							url: EE.BASE+"&"+EE.filebrowser.endpoint_url+"&action=ajax_create_thumb",
							data: "XID=" + EE.XID + '&dir='+directory.id+'&image='+file.name
						});
					}

				}
				else if (j < thumbs_per_page) {
					thumbs += directory.url+'_thumbs/thumb_'+file.name;
				}
				else {
					thumbs += spinner_url;
				}
				
				thumbs += '" alt="thumbnail" /></div>'+file.name+'</div>';
			}

			item_count++;
		});
		
		_pages.push(thumbs)
		thumbs = _pages.join('</div><div class="item">');
		
		api.getItemWrap().append('<div class="item">'+thumbs+'</div>');

		api.reload(); // final recount

		// Since items were added, we need to re-setup the events, but its possible that
		// some already have events assigned. Mass Unbind called for here.
		$(".item > div", file_manager_obj).unbind();

		// setup activity
		$(".item > div", file_manager_obj).click(function() {

			var file,
				is_image = ! ($(this).find("img").attr("src") == default_img_url);

			if (is_image === true) {
				file = {
					is_image: true,
					thumb: $(this).find("img").attr("src"),
					directory: current_directory,
					dimensions: $(this).find("div").attr("title"),
					name: $(this).find("img").attr("title").split("}")[1]
				};
			}
			else {
				file = {
					is_image: false,
					thumb: default_img_url,
					directory: current_directory,
					name: $(this).find("div").attr("title").split("|")[1]
				};
			}

			trigger_callback(file);
			file_manager_obj.dialog("close");
		});

		// If there is only 1 page, we dont need the navigation controls
		if (api.getPageAmount() == 1)
		{
			$("#nav_controls_"+directory.id, file_manager_obj).hide();
		}
		else
		{
			// In case they were hidden and an item was added
			$("#nav_controls_"+directory.id, file_manager_obj).show();
		}
	}

	// --------------------------------------------------------------------

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
	 * In order to save on bandwidth, when the images are loaded into the file browser they
	 * are only placeholders. This function takes care of intelligently loading thumbnails
	 * for your viewing pleasure.
	 */
	function loadThumbs(directory)
	{
		var api = $("#page_"+directory).scrollable();

		// Which page of thumbs do we need? Also, a bit of defensive coding
		page_index = (api.getPageIndex() == "") ? 0 : api.getPageIndex();

		// Go through and grab each image and modify the src now to load the
		// pretty thumb if it needs loading
		if ($("#page_"+directory+" .item:eq("+page_index+") img").length > 0) {
			var sources = {};

			// To create the illusion of "thinking" while the thumbnail is downloading, the thumbnail is first
			// loaded into an empty DOM image. This image is never put anywhere on the page, but forces the
			// browser to cache it. After it is completely loaded into the cache, it will then replace the image.
			$("#page_"+directory+" .item:eq("+page_index+") img").each(function (i) {

				var that = $(this);
				var regex = /^\{filedir_(\d+)\}/;
				var match = regex.exec(that.attr("title"));

				// If it has a spinner we need to swap
				if (that.attr('src') == spinner_url) {

					sources[i] = dir_paths[match[1]]+'/_thumbs/thumb_'+that.attr("title").replace(regex, '');

					$('<img src="'+sources[i]+'" />').load(function(){
						that.attr("src", sources[i]);
					});
				}
				else if (that.attr('class') == "image" && that.attr('src') == default_img_url)
				{
					// What we have here is an image file that the user is viewing that is 
					// without a thumbnail. Let's get to work

					// change thumb to the spinner to indicate we're working on it
					that.attr('src', spinner_url);

					// Get the filename (needed to generate the thumb)
					var filename = that.attr("title").substring(that.attr("title").indexOf("}")+1);

					// Generate it in the background, and if we're successful load it. If
					// thumb creation fails for some reason, drop in the default image.
					$.ajax({
						type: "POST",
						url: EE.BASE+"&"+EE.filebrowser.endpoint_url+"&action=ajax_create_thumb",
						data: "XID=" + EE.XID + '&dir='+directory+'&image='+filename,
						success: function (thumb_src) {
							that.attr('src', dir_paths[match[1]]+'/_thumbs/thumb_'+that.attr("title").replace(regex, ''));
						},
						error: function () {
							that.attr('src', default_img_url);
						}
					});
				}
			});
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
			open: function(event, ui) {
				// keyboard naviation is disabled so form elements are usable,
				// re-initialize it when file browser is open
				$("#file_manager_main").scrollable().getConf().keyboard = "static";
				$("#file_manager_main").scrollable().reload();

				// enable keyboard navigation for pages (will get turned off if the file browser gets hidden)
				$(".vertscrollable").scrollable().getConf().keyboard = true;
				$(".vertscrollable").scrollable().reload();
			},
			close: function(event, ui) {
				$("#file_manager_main").scrollable().getConf().keyboard = false;
				$("#file_manager_main").scrollable().reload();

				// set scrollable back to the first page available
				$("#main_navi li:first").click();
			}
		});


		// Create vertical tabs
		$("#file_manager_main").scrollable({
			vertical: true,
			size: 1,
			clickable: false,
			speed: 250,
			keyboard: false,
			onSeek: function(i) {

				// onSeek (and onBeforeSeek which is the easiest to see this with) are firing twice, I believe
				// because of the nested scrollable plugins. Its my theory that this is intermittently making
				// the "pagination" wrong on the second, third, forth, etc directories.
				// we'll just use a simple variable (cur_dir_seek) to make sure this doesn't fire twice. Also making
				// efforts to get this patched in the scrollable plugin proper.

				if (cur_dir_seek != i)
				{
					cur_dir_seek = i;

					// In order to figure out which (of potentially dozens) directory we are on, we need to read the DOM
					current_directory = $("li:eq("+i+")", '#main_navi').attr("id").replace(/main_navi_/, "");

					// Are there files that need to be retrieved for this dir?
					// loadFiles will intelligently take care of minimizing HTTP requests
					loadFiles(current_directory);
					loadThumbs(current_directory);

					// An up/down page has changed. Focus in on it
					focused_tab.scrollable(i).focus();

					$("#page_"+current_directory).scrollable().reload();
				}
			}
		}).navigator("#main_navi");


		// Create horizontal pages
		focused_tab = $(".vertscrollable").scrollable({
			size: 1,
			clickable: false,
			nextPage: ".newThumbs",
			prevPage: ".prevThumbs",
			keyboard: false,
			onBeforeSeek: function(i) {
				// Keyboard navigation interferes with the ability to arrow around the form
				// if the file browser isn't showing, turn off keyboard nav
				if ($("#file_manager_main:visible").length == 0)
				{
					this.getConf().keyboard = false;
					this.reload();
				}
			},
			onSeek: function(i) {
				loadThumbs(current_directory);
			}
		}).navigator({navi: ".navi"});

		// load content for default directory
		loadFiles(current_directory);
		loadThumbs(current_directory);

		// set keyboard focus on the first horizontal scrollable 
		focused_tab.eq(0).scrollable().focus();
		
		// Bind the upload submit event
		$("#upload_form", file_manager_obj).submit($.ee_filebrowser.upload_start);
	}

})(jQuery);