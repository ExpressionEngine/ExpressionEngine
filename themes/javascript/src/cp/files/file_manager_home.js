/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */


//"use strict";

$.ee_filemanager = $.filemanager || {};

$(document).ready(function () {
	// Load the functionality needed for this page
	$.ee_filemanager.file_uploader();
	$.ee_filemanager.datatables();
	$.ee_filemanager.image_overlay();
	$.ee_filemanager.date_range();
	$.ee_filemanager.toggle_all();
	$.ee_filemanager.directory_change();

	// Hide first and previous pagination
	$(".paginationLinks .first").hide();
	$(".paginationLinks .previous").hide();
});

$.ee_filemanager.file_uploader = function() {
	$.ee_fileuploader({
		type: 'filemanager',
		load: function() {
			$.template("filemanager_row", $('#filemanager_row').remove());
		},
		open: function(file_uploader) {
			$.ee_fileuploader.set_directory_id($('#dir_id').val());
		},
		after_upload: function(file_uploader, file){
			// if we're replacing remove any visible files with the same ID
			if (file.replace == true) {
				$('.mainTable tbody tr:has(td:contains(' + file.file_id + ')):has(td:contains(' + file.file_name + '))').remove();
			};
			
			// Build actions
			file.actions = '';
			$.each(EE.fileuploader.actions, function(index, val) {
				var current_action = val.replace('[file_id]', file.file_id).replace('[upload_dir]', file.upload_directory_prefs.id);
				
				// Add the edit action only if it's an image
				if (index == "delete") {
					file.action_delete = current_action;
				} else if (index != "image" || file.is_image) {
					file.actions += current_action + '&nbsp;&nbsp;';
				};
			});
			
			if (typeof file.title == "undefined") {
				file.title = file.name;
			};
			
			if (file.is_image) {
				// Build link
				var $link = $('<a>', {
					'id': 		'', 
					'href': 	file.upload_directory_prefs.url + file.file_name,
					'title': 	file.file_name,
					'text': 	file.title,
					'rel': 		'#overlay',
					'class': 	'less_important_link overlay'
				});
				
				// I realize how foolish this looks, but in order to pass the html
				// to jQuery templates, we need the html and jQuery in its infinite
				// wisdom has no method to get the full html of an object, it only
				// has a method to get the inner html, completely missing the actual
				// anchor link, seems worthless to me too.

				file.link = $link.wrap('<div>').parent().html();
			} else {
				file.link = file.title;
			};
			
			// Send it all to the jQuery Template
			$('.mainTable tbody').prepend($.tmpl('filemanager_row', file));
			
			if ($('td.dataTables_empty').size()) {
				$('td.dataTables_empty').parent().remove();
			};

			if (file.replace != true) {
				// Change modal's top
				$('#file_uploader').dialog('option', 'position', 'center');
			};

			// If there were no files previously, the table might be hidden
			$('.mainTable').show();

			// Ensure new file appears on subsequent filtering
			$(".mainTable").table('clear_cache');
		},
		trigger: '#action_nav a.upload_file'
	});
};
	
$.ee_filemanager.directory_change = function() {
	var file_oracle		= EE.file.directoryInfo,
		spaceString		= new RegExp('!-!', "g");

	// We prep our magic arrays as soons as we can, basically
	// converting everything into option elements
	$.each(file_oracle, function(key, details) {

		// Go through each of the individual settings and build a proper dom element
		$.each(details, function(group, values) {
			var html = new String();

			// Add the new option fields
			$.each(values, function(a, b) {
				html += '<option value="' + b[0] + '">' + b[1].replace(spaceString, String.fromCharCode(160)) + "</option>";
			});

			// Set the new values
			file_oracle[key][group] = html;
		});
	});

	// Change the submenus
	// Gets passed the directory id
	function changemenu(index) {
		var dirs = 'null';

		if (file_oracle[index] === undefined) {
			index = 0;
		}

		jQuery.each(file_oracle[index], function(key, val) {
			$('select#cat_id').empty().append(val);

		});
	}

	$("#dir_id").change(function() {
		changemenu(this.value);
	});
};

$.ee_filemanager.date_range = function() {
	$("#custom_date_start_span").datepicker({
		dateFormat: "yy-mm-dd",
		prevText: "<<",
		nextText: ">>",
		onSelect: function(date) { 
			$("#custom_date_start").val(date);
			dates_picked();
		} 
	});

	$("#custom_date_end_span").datepicker({ 
		dateFormat: "yy-mm-dd",
		prevText: "<<",
		nextText: ">>",
		onSelect: function(date) {
			$("#custom_date_end").val(date);
			dates_picked();
		} 
	});

	$("#custom_date_start, #custom_date_end").focus(function(){
		if ($(this).val() == "yyyy-mm-dd") {
			$(this).val("");
		}
	});

	$("#custom_date_start, #custom_date_end").keypress(function(){
		if ($(this).val().length >= 9) {
			dates_picked();
		}
	});

	function dates_picked() {
		if ($("#custom_date_start").val() != "yyyy-mm-dd" && $("#custom_date_end").val() != "yyyy-mm-dd") {
			// populate dropdown box
			focus_number = $("#date_range").children().length;
			$("#date_range").append("<option id=\"custom_date_option\">" + $("#custom_date_start").val() + " to " + $("#custom_date_end").val() + "</option>");
			document.getElementById("date_range").options[focus_number].selected=true;

			// hide custom date picker again
			$("#custom_date_picker").slideUp("fast");
			
			// Trigger change to update filter
			$("#date_range").change();
			
			// redraw table
			oTable.fnDraw();
		}
	}

	$("#date_range").change(function() {
		if ($('#date_range').val() == 'custom_date') {
			// clear any current dates, remove any custom options
			$('#custom_date_start').val('yyyy-mm-dd');
			$('#custom_date_end').val('yyyy-mm-dd');
			$('#custom_date_option').remove();

			// drop it down
			$('#custom_date_picker').slideDown('fast');
		} else {
			$('#custom_date_picker').hide();
		}
	});
};

$.ee_filemanager.toggle_all = function() {
	$(".toggle_all").toggle(
		function(){		
			$("input.toggle").each(function() {
				this.checked = true;
			});
		}, function (){
			var checked_status = this.checked;
			$("input.toggle").each(function() {
				this.checked = false;
			});
		}
	);
};

$.ee_filemanager.image_overlay = function() {
	function show_image() {
		// Destroy any existing overlay
		$('#overlay').hide().removeData('overlay');
		$('#overlay .contentWrap img').remove();

		// Launch overlay once image finishes loading
		$('<img />').appendTo('#overlay .contentWrap').load(function() {

			// We need to scale very large images down just a bit. To do that we
			// need a reference element that we can set to visible very briefly
			// or we won't get a proper width / height
			var ref = $(this).clone().appendTo(document.body).show(),

				w = ref.width(),
				h = ref.height(),

				max_w = $(window).width() * 0.8,			// 10% margin
				max_h = $(window).height() * 0.8,

				rat_w = max_w / w,							// ratios
				rat_h = max_h / h,

				ratio = (rat_w > rat_h) ? rat_h : rat_w;	// use the smaller

			ref.remove();

			// We only scale down - up would be silly
			if (ratio < 1) {
				h = h * ratio;
				w = w * ratio;

				$(this).height(h).width(w);
			}

			$('#overlay').overlay({
				load: true,
				speed: 100,
				top: 'center'
			});
		})

		.attr('src', $(this).attr('href')); // start loading

		// Prevent default click event
		return false;
	}

	// Set up image viewer (overlay)
	$('a.overlay').live('click', show_image);
	$('#overlay').css('cursor', 'pointer').click(function() {
		$(this).fadeOut(100);
	});
};

$.ee_filemanager.datatables = function() {

	$(".mainTable").table('add_filter', $('#filterform'));
	
};