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
			
			// Clone the first valid row
			var $first_row = $('.mainTable tbody tr:first').clone();
			
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
				// to jQuery templates, we need the html and jQuery in it's infinite
				// wisodom has no method to get the full html of an object, it only
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
	var oCache = {
		iCacheLower: -1
	};

	function fnSetKey( aoData, sKey, mValue ) {
		for ( var i=0, iLen=aoData.length; i < iLen ; i++ ) {
			if ( aoData[i].name == sKey ) {
				aoData[i].value = mValue;
			}
		}
	}


	function fnGetKey( aoData, sKey ) {
		for ( var i=0, iLen=aoData.length; i < iLen ; i++ ) {
			if ( aoData[i].name == sKey ) {
				return aoData[i].value;
			}
		}
		return null;
	}

	function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
		var iPipe			= +EE.file.pipe,  /* Ajust the pipe size */
			bNeedServer		= false,
			sEcho			= fnGetKey(aoData, "sEcho"),
			iRequestStart	= fnGetKey(aoData, "iDisplayStart"),
			iRequestLength	= fnGetKey(aoData, "iDisplayLength"),
			iRequestEnd		= iRequestStart + iRequestLength,
			keywords		= document.getElementById("keywords"),
			type			= document.getElementById("file_type"),
			dir_id			= document.getElementById("dir_id"),
			cat_id			= document.getElementById("cat_id"),
			date_range		= document.getElementById("date_range"),
			search_in		= document.getElementById("search_in"),
			file_type		= document.getElementById("file_type");

		// for browsers that don't support the placeholder
		// attribute. See global.js :: insert_placeholders()
		// for more info. -pk
		function keywords_value() {
			if ($(keywords).data('user_data') == 'n') {
				return '';
			}

			return keywords.value;
		}



		aoData.push( 
			{ "name": "keywords", "value": keywords_value() },
			{ "name": "type", "value": type.value },
			{ "name": "dir_id", "value": dir_id.value },
			{ "name": "cat_id", "value": cat_id.value },
			{ "name": "date_range", "value": date_range.value },
			{ "name": "search_in", "value": search_in.value },
			{ "name": "file_type", "value": file_type.value }
		 );

		oCache.iDisplayStart = iRequestStart;

		/* outside pipeline? */
		if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper ) {
			bNeedServer = true;
		}

		/* sorting etc changed? */
		if ( oCache.lastRequest && !bNeedServer ) {
			for( var i=0, iLen=aoData.length ; i<iLen ; i++ ) {
				if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" ) {
					if ( aoData[i].value != oCache.lastRequest[i].value ) {
						bNeedServer = true;
						break;
					}
				}
			}
		}

		/* Store the request for checking next time around */
		oCache.lastRequest = aoData.slice();

		if ( bNeedServer ) {
			if ( iRequestStart < oCache.iCacheLower ) {
				iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
				if ( iRequestStart < 0 ) {
					iRequestStart = 0;
				}
			}

			oCache.iCacheLower = iRequestStart;
			oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
			oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
			fnSetKey( aoData, "iDisplayStart", iRequestStart );
			fnSetKey( aoData, "iDisplayLength", iRequestLength * iPipe );

					aoData.push(  
						{ "name": "keywords", "value": keywords_value() },
						{ "name": "type", "value": type.value },
						{ "name": "dir_id", "value": dir_id.value },
						{ "name": "cat_id", "value": cat_id.value },
						{ "name": "date_range", "value": date_range.value },
						{ "name": "search_in", "value": search_in.value },
						{ "name": "file_type", "value": file_type.value }
					);

			$.getJSON( sSource, aoData, function (json) { 
				/* Callback processing */
				oCache.lastJson = jQuery.extend(true, {}, json);

				if ( oCache.iCacheLower != oCache.iDisplayStart ) {
					json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
				}
				json.aaData.splice( oCache.iDisplayLength, json.aaData.length );

				fnCallback(json);
			});
		} else {
			json = jQuery.extend(true, {}, oCache.lastJson);
			json.sEcho = sEcho; /* Update the echo for each response */
			json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
			json.aaData.splice( iRequestLength, json.aaData.length );
			fnCallback(json);
			return;
		}
	}

// name, size, kind, date, actions, toggle
// id, title, file name, kind, dir name, comments, date, actions, toggle

	if (EE.file.tableColumns == 9) {
		MyCols = [null, null, null, null, null, null, null, { "bSortable" : false }, { "bSortable" : false }, { "bSortable" : false } ];
		MySortCol = 6;
	} else {
		MyCols = [null, null, null, null, null, null, { "bSortable" : false }, { "bSortable" : false }, { "bSortable" : false } ];
		MySortCol = 5;
	}

	oTable = $("#file_form .mainTable").dataTable({ 
		"sPaginationType": "full_numbers",
		"bLengthChange": false,
		"aaSorting": [[ MySortCol, "desc" ], [0, "desc"]],
		"bFilter": false,
		"sWrapper": false,
		"sInfo": false,
		"bAutoWidth": false,
		"iDisplayLength": +EE.file.perPage,	 
		"aoColumns": MyCols,
		"oLanguage": {
			"sZeroRecords": EE.lang.noEntries,
			"oPaginate": {
				"sFirst": "<img src=\""+EE.file.themeUrl+"images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\""+EE.file.themeUrl+"images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\""+EE.file.themeUrl+"images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\""+EE.file.themeUrl+"images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": EE.BASE+"&C=content_files&M=file_ajax_filter&time=" + new Date().getTime(),
		"fnServerData": fnDataTablesPipeline
	});

	$("#keywords").keyup(function () {
		/* Filter on the column (the index) of this element */
		oTable.fnDraw();
	});

	$("select#dir_id, select#cat_id, select#file_type, select#date_range").change(function () {
		oTable.fnDraw();
	});
};