/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
var selField  = false,
	selMode = "normal";

//Dynamically set the textarea name
function setFieldName(which)
{
	if (which != selField) {
		selField = which;
		clear_state();

		tagarray  = new Array();
		usedarray = new Array();
		running	  = 0;
	}
}

// Insert tag
function taginsert(item, tagOpen, tagClose)
{
	// Determine which tag we are dealing with
	var which = eval('item.name');

	if ( ! selField) {
		$.ee_notice(no_cursor);
		return false;
	}

	var theSelection	= false,
		result			= false,
		theField		= document.getElementById('entryform')[selField];

	if (selMode == 'guided') {
		data = prompt(enter_text, "");

		if ((data != null) && (data != "")) {
			result =  tagOpen + data + tagClose;
		}
	}

	// Is this a Windows user?
	// If so, add tags around selection

	if (document.selection) {
		theSelection = document.selection.createRange().text;
		theField.focus();

		if (theSelection) {
			document.selection.createRange().text = (result == false) ? tagOpen + theSelection + tagClose : result;
		} else {
			document.selection.createRange().text = (result == false) ? tagOpen + tagClose : result;
		}

		theSelection = '';

		theField.blur();
		theField.focus();

		return;
	} else if ( ! isNaN(theField.selectionEnd)) {
		var newStart,
			scrollPos = theField.scrollTop,
			selLength = theField.textLength,
			selStart = theField.selectionStart,
			selEnd = theField.selectionEnd;

		if (selEnd <= 2 && typeof(selLength) != 'undefined') { 
			selEnd = selLength;
		}

		var s1 = (theField.value).substring(0,selStart),
			s2 = (theField.value).substring(selStart, selEnd).
			s3 = (theField.value).substring(selEnd, selLength);

		if (result == false) {
			newStart = selStart + tagOpen.length + s2.length + tagClose.length;
			theField.value = (result == false) ? s1 + tagOpen + s2 + tagClose + s3 : result;
		} else {
			newStart = selStart + result.length;
			theField.value = s1 + result + s3;
		}

		theField.focus();
		theField.selectionStart = newStart;
		theField.selectionEnd = newStart;
		theField.scrollTop = scrollPos;
		return;
	} else if (selMode == 'guided') {
		curField = document.submit_post[selfField];

		curField.value += result;
		curField.blur();
		curField.focus();

		return;
	}

	// Add single open tags
	if (item == 'other') {
		eval("document.getElementById('entryform')." + selField + ".value += tagOpen");
	} else if (eval(which) == 0) {
		var result = tagOpen;

		eval("document.getElementById('entryform')." + selField + ".value += result");
		eval(which + " = 1");

		arraypush(tagarray, tagClose);
		arraypush(usedarray, which);

		running++;

		styleswap(which);
	} else {
		// Close tags

		n = 0;

		for (i = 0 ; i < tagarray.length; i++ ) {
			if (tagarray[i] == tagClose) {
				n = i;
				running--;
				while (tagarray[n]) {
					closeTag = arraypop(tagarray);
					eval("document.getElementById('entryform')." + selField + ".value += closeTag");
				}
				while (usedarray[n]) {
					clearState = arraypop(usedarray);
					eval(clearState + " = 0");
					document.getElementById(clearState).className = 'htmlButtonA';
				}
			}
		}

		if (running <= 0 && document.getElementById('close_all').className == 'htmlButtonB') {
			document.getElementById('close_all').className = 'htmlButtonA';
		}
	}

	curField = eval("document.getElementById('entryform')." + selField);
	curField.blur();
	curField.focus();
}

$(document).ready(function() {
	$(".js_show").show();
	$(".js_hide").hide();

	if (EE.publish.markitup !== undefined && EE.publish.markitup.fields !== undefined) {
		$.each(EE.publish.markitup.fields, function(key, value) { 
			$("#"+key).markItUp(mySettings);
		});
	}

	if (EE.publish.smileys === true) {
		
		$("a.glossary_link").click(function(){
			$(this).parent().siblings('.glossary_content').slideToggle("fast");
			$(this).parent().siblings('.smileyContent .spellcheck_content').hide();
			return false;
		});
	 
		$('a.smiley_link').toggle(function() {
			which = $(this).attr('id').substr(12);
			$('#smiley_table_'+which).slideDown('fast', function() { 
				$(this).css('display', '');
			});
			}, function() {
				$('#smiley_table_'+which).slideUp('fast');
			});
	
			$(this).parent().siblings('.glossary_content, .spellcheck_content').hide();
	
			$('.glossary_content a').click(function(){
				$.markItUp({ replaceWith:$(this).attr('title')});
				return false;
			});
	}
	
	$(".btn_plus a").click(function(){
		return confirm(EE.lang.confirm_exit, "");
	});
	
	// inject the collapse button into the formatting buttons list
	$(".markItUpHeader ul").prepend("<li class=\"close_formatting_buttons\"><a href=\"#\"><img width=\"10\" height=\"10\" src=\""+EE.THEME_URL+"images/publish_minus.gif\" alt=\"Close Formatting Buttons\"/></a></li>");
	
	$(".close_formatting_buttons a").toggle(
		function() {
			$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();
			$(this).parent().parent().css("height", "13px");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_plus.png");
		}, function () {
			$(this).parent().parent().children().show();
			$(this).parent().parent().css("height", "auto");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_minus.gif");
		}
	);
	
	$.ee_filebrowser();
	
	var field_for_writemode_publish = "";
	
	if (EE.publish.show_write_mode === true) { 
		$("#write_mode_textarea").markItUp(myWritemodeSettings);		
	}

	$(".write_mode_trigger").click(function(){

		if ($(this).attr("id").match(/^id_\d+$/)) {
			field_for_writemode_publish = "field_"+$(this).attr("id");
		} else {
			field_for_writemode_publish = $(this).attr("id").replace(/id_/, '');
		}

		// put contents from other page into here
		$("#write_mode_textarea").val($("#"+field_for_writemode_publish).val());
		$("#write_mode_textarea").focus();
		return false;
	});
	
	// Prep for a workaround to allow markitup file insertion in file inputs
	$(".btn_img a, .file_manipulate").click(function(){
		window.file_manager_context = ($(this).parent().attr("class").indexOf("markItUpButton") == -1) ? 	$(this).closest("div").find("input").attr("id") : "textarea_a8LogxV4eFdcbC";
	});
	
	function file_field_changed(file, field) {
		var container = $("input[name="+field+"]").closest(".publish_field");

		if (file.is_image == false) {
			container.find(".file_set").show().find(".filename").html("<img src=\""+EE.PATH_CP_GBL_IMG+"default.png\" alt=\""+EE.PATH_CP_GBL_IMG+"default.png\" /><br />"+file.name);
		}
		else
		{
			container.find(".file_set").show().find(".filename").html("<img src=\""+file.thumb+"\" alt=\""+file.name+"\" /><br />"+file.name);
		}

		$("input[name="+field+"_hidden]").val(file.name);
		$("select[name="+field+"_directory]").val(file.directory);

		$.ee_filebrowser.reset(); // restores everything to "default" state - also needed above for textareas
	}
	
	// $("#publishForm input[type=file]").each(function() {
	// 	var container = $(this).closest(".publish_field"),
	// 		trigger = container.find(".choose_file"),
	// 		content_type = $(this).data('content-type'),
	// 		directory = $(this).data('directory'),
	// 		settings = {
	// 			"content_type": content_type,
	// 			"directory": directory
	// 		};
	// 
	// 	$.ee_filebrowser.add_trigger(trigger, $(this).attr("name"), settings, file_field_changed);
	// 
	// 	container.find(".remove_file").click(function() {
	// 		container.find("input[type=hidden]").val("");
	// 		container.find(".file_set").hide();
	// 		return false;
	// 	});
	// });
	
	// Bind the image html buttons
	$.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate", function(file) {
		// We also need to allow file insertion into text inputs (vs textareas) but markitup
		// will not accommodate this, so we need to detect if this request is coming from a 
		// markitup button (textarea_a8LogxV4eFdcbC), or another field type.
		if (window.file_manager_context == "textarea_a8LogxV4eFdcbC") {
			// Handle images and non-images differently
			if ( ! file.is_image) {
				$.markItUp({name:"Link", key:"L", openWith:"<a href=\"{filedir_"+file.directory+"}"+file.name+"\">", closeWith:"</a>", placeHolder:file.name });
			} else {
				$.markItUp({ replaceWith:"<img src=\"{filedir_"+file.directory+"}"+file.name+"\" alt=\"[![Alternative text]!]\" "+file.dimensions+"/>" } );}
			} else {
				$("#"+window.file_manager_context).val("{filedir_"+file.directory+"}"+file.name);
			}
		});
		
		$("input[type=file]", "#publishForm").each(function() {
			var container = $(this).closest(".publish_field"),
				trigger = container.find(".choose_file");
	
			$.ee_filebrowser.add_trigger(trigger, $(this).attr("name"), file_field_changed);
	
			container.find(".remove_file").click(function() {
			container.find("input[type=hidden]").val("");
			container.find(".file_set").hide();
			return false;
		});
	});
});