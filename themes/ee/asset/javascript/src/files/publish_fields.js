/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// Fire off the file browser
$.ee_filebrowser();

// Make sure we can create these methods without issues
EE.namespace('EE.publish.file_browser');

(function($) {
	/**
	 * Fires up the filebrowser for text areas
	 */
	EE.publish.file_browser.textarea = function(context) {
		// Bind the image html buttons
		$.ee_filebrowser.add_trigger($(".btn_img a, .file_manipulate", context), function(file) {
			var textarea,
				replace = '',
				props = '',
				open = '',
				close = '';

			button_id = $(this).parent().attr('class').match(/id(\d+)/);
			if (button_id != null)
			{
				button_id = button_id[1];
			}

			if (context !== undefined)
			{
				textarea = $('textarea', context);
				textarea.focus();
			}
			else
			{
				// A bit of working around various textareas, text inputs, tec
				if ($(this).closest("#markItUpWrite_mode_textarea").length) {
					textareaId = "write_mode_textarea";
				} else {
					textareaId = $(this).closest(".publish_field").attr("id").replace("hold_field_", "field_id_");
				}

				if (textareaId != undefined) {
					textarea = $("textarea[name="+textareaId+"], input[name="+textareaId+"]", context);
					textarea.focus();
				}
			}

			// We also need to allow file insertion into text inputs (vs textareas) but markitup
			// will not accommodate this, so we need to detect if this request is coming from a
			// markitup button or another field type.

			// Fact is - markitup is actually pretty crappy for anything that doesn't specifically
			// use markitup. So currently the image button only works correctly on markitup textareas.

			if ( ! file.is_image) {
				props = EE.upload_directories[file.upload_location_id].file_properties;

				open = EE.upload_directories[file.upload_location_id].file_pre_format;
				open += "<a href=\"{filedir_"+file.upload_location_id+"}"+file.file_name+'" '+props+" >";

				close = "</a>";
				close += EE.upload_directories[file.upload_location_id].file_post_format;
			} else {
				props = EE.upload_directories[file.upload_location_id].properties;

				open = EE.upload_directories[file.upload_location_id].pre_format;
				close = EE.upload_directories[file.upload_location_id].post_format;

				image_tag = (button_id == null) ? EE.filebrowser.image_tag : EE.filebrowser['image_tag_'+button_id];

				// Include any user additions before or after the image link
				replace = image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/, 'src="$1{filedir_'+file.upload_location_id+'}'+file.file_name+'$2"');

				// Figure out dimensions
				dimensions = '';
				if (typeof file.file_hw_original != "undefined" && file.file_hw_original != '') {
					dimensions = file.file_hw_original.split(' ');
					dimensions = 'height="'+dimensions[0]+'" width="'+dimensions[1]+'"';
				};

				replace = replace.replace(/\/?>$/, dimensions+' '+props+' />');

				replace = open + replace + close;
			}


			if (textarea.is("textarea")) {
				if ( ! textarea.is('.markItUpEditor')) {
					textarea.markItUp(myNobuttonSettings);
					textarea.closest('.markItUpContainer').find('.markItUpHeader').hide();
					textarea.focus();
				}

				// Handle images and non-images differently
				if ( ! file.is_image) {
					$.markItUp({
						key:"L",
						name:"Link",
						openWith: open,
						closeWith: close,
						placeHolder:file.file_name
					});
				} else {
					$.markItUp({
						replaceWith: replace
					});
				}
			} else {
				textarea.val(function(i, v) {
					v += open + replace + close;
					return magicMarkups(v);
				});
			}
		});
	};

	// @todo rewrite dependencies and remove
	function magicMarkups(string) {
		var abort = false;

		if (string) {
			string = string.toString();
			string = string.replace(/\(\!\(([\s\S]*?)\)\!\)/g,
				function(x, a) {
					var b = a.split('|!|');
					if (altKey === true) {
						return (b[1] !== undefined) ? b[1] : b[0];
					} else {
						return (b[1] === undefined) ? "" : b[0];
					}
				}
			);
			// [![prompt]!], [![prompt:!:value]!]
			string = string.replace(/\[\!\[([\s\S]*?)\]\!\]/g,
				function(x, a) {
					var b = a.split(':!:');
					if (abort === true) {
						return false;
					}
					value = prompt(b[0], (b[1]) ? b[1] : '');
					if (value === null) {
						abort = true;
					}
					return value;
				}
			);
			return string;
		}
		return "";
	}

	/**
	 * Changes the hidden inputs, thumbnail and file name when a file is selected
	 * @private
	 * @param {Object} file File object with information about the file upload
	 * @param {Object} field jQuery object of the field
	 */
	function file_field_changed(file, field) {
		var container = $("input[name='"+field+"']").closest('.file_field');

		if (file.is_image == false) {
			container.find(".file_set").show().find(".filename").html("<img src=\""+EE.PATH_CP_GBL_IMG+"default.png\" alt=\""+EE.PATH_CP_GBL_IMG+"default.png\" /><br />"+file.file_name);
		} else {
			container.find(".file_set").show().find(".filename").html("<img src=\""+file.thumb+"\" /><br />"+file.file_name);
		}

		container.find('.choose_file').hide();
		container.find('.undo_remove').hide();

		container.find('input[name*="_hidden_file"]').val(file.file_name);
		container.find('input[name*="_hidden_dir"], select[name*="_directory"]').val(file.upload_location_id);
	}

	/**
	 * Given a selector and context, creates file browser triggers for multiple elements
	 * @private
	 * @param {String} selector The jQuery selector you're looking for,
	 *		representing the link to open the file browser
	 * @param {String} selector The jQuery selector representing the context in
	 *		which to search for the selector
	 */
	function add_trigger(selector, context) {
		// Look for every file input on the publish form and establish the
		// file browser trigger. Also establishes the remove file handler.
		$(selector, context).each(function() {
			var container = $(this).closest('.file_field'),
				trigger = container.find(".choose_file"),
				no_filemanager = container.find('.no_file'),
				content_type = $(this).data('content-type'),
				directory = $(this).data('directory'),
				last_value = [], // used for undo
				settings = {
					"content_type": content_type,
					"directory": directory
				};

			$.ee_filebrowser.add_trigger(trigger, $(this).attr("name"), settings, file_field_changed);

			fileselector = trigger.length ? trigger : no_filemanager;

			container.find(".remove_file").click(function() {
				container.find("input[type=hidden]").val(function(i, current_value) {
					last_value[i] = current_value;
					return '';
				});
				container.find(".file_set").hide();
				container.find('.sub_filename a').show();
				fileselector.show();

				return false;
			});

			container.find('.undo_remove').click(function() {
				container.find("input[type=hidden]").val(function(i) {
					return last_value.length ? last_value[i] : '';
				});
				container.find(".file_set").show();
				container.find('.sub_filename a').hide();
				fileselector.hide();

				return false;
			});
		});
	};

	/**
	 * Fire up the file browser for file fields
	 */
	EE.publish.file_browser.file_field = function() {
		add_trigger("input[type=file]", "#publishForm .publish_file, .pageContents");

		// Bind a new trigger when a new Grid row is added
		Grid.bind('file', 'display', function(cell)
		{
			add_trigger('input[type=file]', cell);
		});
	};

	/**
	 * Creates file browser trigger for the category edit modal
	 */
	EE.publish.file_browser.category_edit_modal = function() {
		add_trigger('input[type=file]', '#cat_modal_container');
	};

	$(function() {
		if (EE.filebrowser.publish) {
			// Give Markitup time to activate
			setTimeout(function() {
				EE.publish.file_browser.file_field();
				EE.publish.file_browser.textarea();
			}, 15)
		};
	});
})(jQuery);
