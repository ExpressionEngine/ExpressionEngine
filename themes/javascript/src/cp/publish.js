/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, devel: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE */

"use strict";

EE.publish = EE.publish || {};

// The functions in this file are called from within publish if their components
// are needed. So for example EE.publish.category_editor() is called after
// the category menu is constructed.

EE.publish.category_editor = function() {
	var cat_groups = [],
		cat_modal = $('<div />'),
		cat_modal_container = $('<div id="cat_modal_container" />').appendTo(cat_modal),
		cat_groups_containers = {},
		cat_groups_buttons = {},
		cat_list_url = EE.BASE+'&C=admin_content&M=category_editor&group_id=',
		refresh_cats, setup_page, reload, i;

	// IE caches $.load requests, so we need a unique number
	function now() {
		return +new Date();
	}
	
	cat_modal.dialog({
		autoOpen: false,
		height: 450,
		width: 600,
		modal: true
	});

	// Grab all group ids
	$(".edit_categories_link").each(function() {
		var gid = this.href.substr(this.href.lastIndexOf("=") + 1);
		$(this).data("gid", gid);
		cat_groups.push(gid);
	});

	for (i = 0; i < cat_groups.length; i++) {
		cat_groups_containers[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]);
		cat_groups_containers[cat_groups[i]].data("gid", cat_groups[i]);
		cat_groups_buttons[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]).find(".cat_action_buttons").remove();
	}
	
	refresh_cats = function(gid) {
		cat_groups_containers[gid].text("loading...").load(cat_list_url+gid+"&timestamp="+now()+" .pageContents table", function() {
			setup_page.call(cat_groups_containers[gid], cat_groups_containers[gid].html(), false);
		});
	};

	// A function to setup new page events
	setup_page = function(response, require_valid_response) {
		
		var container = $(this),
			gid = container.data("gid");
		
		response = $.trim(response);

		if (container.hasClass('edit_categories_link')) {
			container = $("#cat_group_container_"+gid);
		}
		
		if (response[0] !== '<' && require_valid_response) {
			return refresh_cats(gid);
		}
		
		container.closest(".cat_group_container").find("#refresh_categories").show();
		
		var res = $(response),
			form = res.find("form"),
			submit_button,
			container_form;
		
		if (form.length) {
			cat_modal_container.html(res);
			
			submit_button = cat_modal_container.find("input[type=submit]");
			container_form = cat_modal_container.find("form");
			
			var handle_submit = function(form) {
				var that = form || $(this),
					values = that.serialize(),
					url = that.attr("action");

				$.ajax({
					url: url,
					type: "POST",
					data: values,
					dataType: "html",
					beforeSend: function() {
						container.html(EE.lang.loading);
					},
					success: function(res) {
						res = $.trim(res);
						cat_modal.dialog("close");
						
						if (res[0] == '<') {
							var response = $(res).find(".pageContents table"),
								form = response.find("form");

							if (form.length == 0) {
								container.html(response);
							}

							setup_page.call(container, response, true);
						}
						else {
							setup_page.call(container, res, true);
						}
					},
					error: function(res) {
						cat_modal.dialog("close");
						setup_page.call(container, res.error, true);
					}
				});
				
				return false;
			};
			
			container_form.submit(handle_submit);
			
			var buttons = {};
			buttons[submit_button.remove().attr('value')] = function() {
				handle_submit(container_form);
			}
			
			cat_modal.dialog("open");
			cat_modal.dialog("option", "buttons", buttons);
			
			cat_modal.one('dialogclose', function() {
				refresh_cats(gid);
			});
		}
		else {
			cat_groups_buttons[gid].clone().appendTo(container).show();
		}
		
		return false;
	};

	// And a function to do the work
	reload = function() {
		var gid = $(this).data("gid"),
			resp_filter = ".pageContents";
		
		if ($(this).hasClass("edit_cat_order_trigger") || $(this).hasClass("edit_categories_link")) {
			resp_filter += " table";
		}

		if ( ! gid) {
			gid = $(this).closest(".cat_group_container").data("gid");
		}
		
		cat_groups_containers[gid].text(EE.lang.loading);
		
		$.ajax({
			url: this.href+"&timestamp="+now()+resp_filter,
			success: function(response) {
				var res, filtered_res = '';
			
				response = $.trim(response);
			
				if (response[0] == '<') {
					res = $(response).find(resp_filter);
					filtered_res = $('<div />').append(res).html();
								
					if (res.find('form').length == 0) {
						cat_groups_containers[gid].html(filtered_res);
					}
				}

				setup_page.call(cat_groups_containers[gid], filtered_res, true);
			},
			error: function(response) {
				// Juck @todo flip to JSON parser in jQuery 1.4
				response = eval('(' + response.responseText + ')');
				cat_groups_containers[gid].html(response.error);
				setup_page.call(cat_groups_containers[gid], response.error, true);
			}
		});
		return false;
	};

	// Hijack edit category links to get it off the ground
	$(".edit_categories_link").click(reload);
	
	// Hijack internal links
	$('.cat_group_container a:not(.cats_done)').live('click', reload);

	// Last but not least - update the checkboxes
	$(".cats_done").live("click", function() {
		var that = $(this).closest(".cat_group_container");
		that.text("loading...").load(EE.BASE+"&C=content_publish&M=category_actions&group_id="+that.data("gid")+"&timestamp="+now(), function(response) {
			that.html( $(response).html() );
		});
				
		return false;
	});
};






var selected_tab = "";

function get_selected_tab() {
	return selected_tab;
}

function tab_focus(tab_id)
{
	// If the tab was hidden, this was triggered
	// through the sidebar - show it again!
	if ( ! $(".menu_"+tab_id).parent().is(":visible")) {
		// we need to trigger a click to maintain
		// the delete button toggle state
		$("a.delete_tab[href=#"+tab_id+"]").trigger("click");
	}

	$(".tab_menu li").removeClass("current");
	$(".menu_"+tab_id).parent().addClass("current");
	$(".main_tab").hide();
	$("#"+tab_id).fadeIn("fast");
	$(".main_tab").css("z-index", "");
	$("#"+tab_id).css("z-index", "5");
	selected_tab = tab_id;
	
	$(".main_tab").sortable("refreshPositions");
}

// @todo hacky, hacky, hacky
EE.tab_focus = tab_focus;

function setup_tabs() {
	var spring_delay = 500,
		focused_tab = "menu_publish_tab",
		field_dropped = false,
		spring = "";

	// allow sorting of publish fields
	$(".main_tab").sortable({
		connectWith: '.main_tab',
		appendTo: '#holder',
		helper: 'clone',
		forceHelperSize: true,
		handle: ".handle",
		start: function(event, ui) {
			ui.item.css("width", $(this).parent().css("width"));
		},
		stop: function(event, ui) {
			ui.item.css("width", "100%");
		}
	});

	$(".tab_menu li a").droppable({
		accept: ".field_selector, .publish_field",
		tolerance: "pointer",
		forceHelperSize: true,
		deactivate: function(e, ui) {
			clearTimeout(spring);
			$(".tab_menu li").removeClass("highlight_tab");
		},
		drop: function(e, ui) {
			field_id = ui.draggable.attr("id").substring(11);
			tab_id = $(this).attr("title").substring(5);

			$("#hold_field_"+field_id).prependTo("#"+tab_id);
			$("#hold_field_"+field_id).hide().slideDown();

			// bring focus
			tab_focus(tab_id);
			return false;
		},
		over: function(e, ui) {

			tab_id = $(this).attr("title").substring(5);
			$(this).parent().addClass("highlight_tab");
				spring = setTimeout(function() {
				tab_focus(tab_id);
				return false;
			}, spring_delay);
		},
		out: function(e, ui) {
			if (spring != "") {
				clearTimeout(spring);
			}
			$(this).parent().removeClass("highlight_tab");
		}
	});

	$("#holder .main_tab").droppable({
		accept: ".field_selector",
		tolerance: "pointer",
		drop: function(e, ui) {
			field_id = (ui.draggable.attr("id") == "hide_title" || ui.draggable.attr("id") == "hide_url_title") ? ui.draggable.attr("id").substring(5) : ui.draggable.attr("id").substring(11);
						tab_id = $(this).attr("id");

			// store the field we are moving, then remove it from the DOM
			$("#hold_field_"+field_id).prependTo("#"+tab_id);// + " div.insertpoint");

			$("#hold_field_"+field_id).hide().slideDown();
		}
	});

	$(".tab_menu li.content_tab a, #publish_tab_list a.menu_focus")
		.unbind(".publish_tabs")
		.bind("mousedown.publish_tabs", function(e) {
			tab_id = $(this).attr("title").substring(5);
			tab_focus(tab_id);
			e.preventDefault();
		}).bind("click.publish_tabs", function() {
			return false;
		});
}

setup_tabs();



EE.publish.save_layout = function() {
	
	var tab_count = 0,
		layout_object = {},
		layout_hidden = {},
		layout_settings = {},
		field_index = 0,
		merge = false,
		hidden_index = 0,
		adjust_index = 0,
		mypre = '_tab_label',
		cur_tab	= $("#tab_menu_tabs li.current").attr("id");

	// for width() to work, the element cannot be in a parent div that is display:none
	$(".main_tab").show();

	//$("li:visible", "#tab_menu_tabs").each(function() {
	$("#tab_menu_tabs a:not(.add_tab_link)").each(function() {

		// skip list items with no id (ie: new tab)
		if ($(this).parent('li').attr('id') && $(this).parent('li').attr('id').substring(0,5) == "menu_")
		{
			var tab_name =  $(this).parent('li').attr('id').substring(5), //$(this).text(),
				tab_id	 = $(this).parent('li').attr('id').substring(5), //$(this).text().replace(/ /g, '_').toLowerCase();
				tab_label = $(this).parent('li').attr('title'); //$(this).text();
			field_index = 0;
			visible = true;
			
			if( $(this).parent('li').is(':visible') )
			{
				lay_name = tab_name;
				layout_object[lay_name] = {};
				layout_object[lay_name][mypre] = tab_label;
			} else {
				merge = true;
				visible = false;
			}
			
			$("#"+tab_id).find(".publish_field").each(function() {

				var that = $(this),
					id = this.id.replace(/hold_field_/, ""),
					percent_width = Math.round((that.width() / that.parent().width()) * 10) * 10,
					temp_buttons = $("#sub_hold_field_"+id+" .markItUp ul li:eq(2)"),
					layout_settings;
					
				if (temp_buttons.html() !== "undefined" && temp_buttons.css("display") !== "none") {
					temp_buttons = true;
				}
				else {
					temp_buttons = false;
				}
				
				layout_settings = {
					visible		: ($(this).css("display") === "none" || visible === false) ? false : true,
					collapse	: ($("#sub_hold_field_"+id).css("display") === "none") ? true : false,
					htmlbuttons	: temp_buttons,
					width		: percent_width+'%'
				};
				
				if (visible === true) {
					layout_settings['index'] = field_index;
					layout_object[lay_name][id] = layout_settings;

					field_index += 1;				
				} else {
					layout_hidden[id] = layout_settings;
				}
				
			});
			
			if (visible === true) {
				tab_count++; // add one to the tab count
			}
		}
	});

	if (merge == true)
	{
		// Add hidden fields to first tab
		
		var darn1, darn2, first_tab, last_index = 0;
		
		for (darn in layout_object) {
			first_tab = darn;
			for (darn2 in layout_object[first_tab]) {
				if (layout_object[first_tab][darn2]['index'] > last_index) {
					last_index = layout_object[first_tab][darn2]['index'];
				}
			}				
			break;
		}

		
		// Reindex first tab
		$.each(layout_hidden, function() {
			this['index'] = ++last_index;
		});
		
		jQuery.extend(layout_object[first_tab], layout_hidden);
	} 
	
	//alert(JSON.stringify(layout_object, null, '\t'));

	// @todo not a great solution
	EE.tab_focus(cur_tab.replace(/menu_/, ""));

	if (tab_count === 0) {
		$.ee_notice(EE.publish.lang.tab_count_zero, {"type" : "error"});
	}
	else if ($("#layout_groups_holder input:checked").length === 0) {
		$.ee_notice(EE.publish.lang.no_member_groups, {"type" : "error"});
	}
	else {
		$.ajax({
			type: "POST",
			dataType: 	'json',
			url: EE.BASE+"&C=content_publish&M=save_layout",
			data: "XID="+EE.XID+"&json_tab_layout="+JSON.stringify(layout_object)+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,
			success: function(result){
				if (result.messageType === 'success') {
					$.ee_notice(result.message, {type: "success"});
				} else if (result.messageType === 'failure') {
					$.ee_notice(result.message, {type: "error"});
				}
			}
		});
	}
};


EE.publish.remove_layout = function() {
	if ($("#layout_groups_holder input:checked").length === 0) {
		return $.ee_notice(EE.publish.lang.no_member_groups, {"type" : "error"});
	}

	var json_tab_layout = "{}"; // empty array will remove everything nicely

	$.ajax({
		type: "POST",
		url: EE.BASE+"&C=content_publish&M=save_layout",
		data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id+"&field_group="+EE.publish.field_group,
		success: function(msg){
			$.ee_notice(EE.publish.lang.layout_removed + " <a href=\"javascript:location=location\">"+EE.publish.lang.refresh_layout+"</a>", {duration:0, type:"success"});
		}
	});
};

EE.date_obj_time = (function() {
	var date_obj = new Date(),
		date_obj_hours = date_obj.getHours(),
		date_obj_mins = date_obj.getMinutes(),
		date_obj_am_pm = "";

	if (date_obj_mins < 10) {
		date_obj_mins = "0" + date_obj_mins;
	}

	if (EE.date.format == "us") {
		if (date_obj_hours > 11) {
			date_obj_hours = date_obj_hours - 12;
			date_obj_am_pm = " PM";
		} else {
			date_obj_am_pm = " AM";
		}
	}
	
	return " '" + date_obj_hours + ":" + date_obj_mins + date_obj_am_pm + "'";
}());


file_manager_context = "";	// @todo - yuck, should be on the EE global


function disable_fields(state) {
	
	var fields = $(".main_tab input, .main_tab textarea, .main_tab select, #submit_button"),
		submit = $("#submit_button"),
		admin_link = $("#holder").find('a');

	if (state) {
		fields.attr("disabled", true);
		submit.addClass("disabled_field");
		admin_link.addClass("admin_mode");
		$("#holder div.markItUp, #holder p.spellcheck").each(function() {
			$(this).before("<div class=\"cover\" style=\"position:absolute;width:100%;height:50px;z-index:9999;\"></div>").css({});
		});
	}
	else {
		fields.removeAttr("disabled");
		submit.removeClass("disabled_field");
		admin_link.removeClass("admin_mode");
		$(".cover").remove();
	}
}

function liveUrlTitle()
{
	var defaultTitle = EE.publish.default_entry_title,
		separator = EE.publish.word_separator,
		newText = document.getElementById("title").value || '',
		replaceField = document.getElementById("url_title"),
		multiReg = new RegExp(separator + '{2,}', 'g'),
		separatorReg = (separator !== '_') ? /\_/g : /\-/g,
		newTextTemp = '',
		pos, c;
	
	if (defaultTitle !== '') {
		if (newText.substr(0, defaultTitle.length) === defaultTitle) {
			newText = newText.substr(defaultTitle.length);
		}
	}
	
	newText = EE.publish.url_title_prefix + newText;
	newText = newText.toLowerCase().replace(separatorReg, separator);

	// Foreign Character Attempt

	for (pos = 0; pos < newText.length; pos++)
	{
		c = newText.charCodeAt(pos);

		if (c >= 32 && c < 128) {
			newTextTemp += newText.charAt(pos);
		}
		else if (c in EE.publish.foreignChars) {
			newTextTemp += EE.publish.foreignChars[c];
		}
	}

	newText = newTextTemp;

	newText = newText.replace('/<(.*?)>/g', '');
	newText = newText.replace(/\s+/g, separator);
	newText = newText.replace(/\//g, separator);
	newText = newText.replace(/[^a-z0-9\-\._]/g, '');
	newText = newText.replace(/\+/g, separator);
	newText = newText.replace(multiReg, separator);
	newText = newText.replace(/^[-_]|[-_]$/g, '');
	newText = newText.replace(/\.+$/g, '');

	if (replaceField) {
		replaceField.value = newText.substring(0,75);
	}
}



$(document).ready(function() {
		
	var autosave_entry,
		start_autosave;

	$("#layout_group_submit").click(function(){
		EE.publish.save_layout();
		return false;
	});

	$("#layout_group_remove").click(function(){
		EE.publish.remove_layout();
		return false;
	});

	$("a.reveal_formatting_buttons").click(function(){
		$(this).parent().parent().children('.close_container').slideDown();
		$(this).hide();
		return false;
	});

	$("#write_mode_header .reveal_formatting_buttons").hide();

	if (EE.publish.smileys == true) {
		$("a.glossary_link").click(function(){
			$(this).parent().siblings('.glossary_content').slideToggle("fast");$(this).parent().siblings('.smileyContent .spellcheck_content').hide();
			return false;
		});

		$('a.smiley_link').toggle(function() {
			$(this).parent().siblings('.smileyContent').slideDown('fast', function() { $(this).css('display', ''); });
		}, function() {
			$(this).parent().siblings('.smileyContent').slideUp('fast');
		});

		$(this).parent().siblings('.glossary_content, .spellcheck_content').hide();

		$('.glossary_content a').click(function(){
			var parent_div = $(this).closest('.publish_field'),
				field_id = parent_div.attr('id').replace('hold_field_', 'field_id_');
			parent_div.find('#'+field_id).insertAtCursor( $(this).attr('title') );
			
			return false;
		});
	}

	if (EE.publish.autosave) {
		
		var autosaving = false;
		
		start_autosave = function() {
			if (autosaving) {
				return;
			}
			
			autosaving = true;
			setTimeout(autosave_entry, 1000 * EE.publish.autosave.interval); // 1000 milliseconds per second
		}
		
		autosave_entry = function() {
			var tools = $("#tools:visible"),
				form_data;
			
			// If the sidebar is showing, then form fields are disabled. Skip it.
			if (tools.length === 1) {
				start_autosave();
				return;
			}
			
			form_data = $("#publishForm").serialize();
			
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: EE.BASE+"&C=content_publish&M=autosave",
				data: form_data,
				success: function(result) {
					if (result.error) {
						console.log(result.error);
					}
					else if (result.success) {
						if (result.autosave_entry_id) {
							$('input[name=autosave_entry_id]').val(result.autosave_entry_id);
						}
						$('#autosave_notice').text(result.success);
					}
					else {
						console.log('Autosave Failed');
					}
					
					autosaving = false;
				}
			});
		};
		
		// Start autosave when something changes
		var writeable = $('textarea, input').not(':password,:checkbox,:radio,:submit,:button,:hidden'),
			changeable = $('select, :checkbox, :radio, :file');
		
		writeable.bind('keypress change', start_autosave);
		changeable.bind('change', start_autosave);
	}


	// Pages URI Placeholder
	if (EE.publish.pages) {
		var pagesUri		= $("#pages__pages_uri"),
			placeholderText = EE.publish.pages.pagesUri;

		if ( ! pagesUri.value) {
			pagesUri.val(placeholderText);
		}

		pagesUri.focus(function() {					
			if (this.value === placeholderText) {
				$(this).val("");
			}	
		}).blur(function() {
			if (this.value === "") {
				$(this).val(placeholderText);
			}
		});		
	}


	$.ee_filebrowser();
	
	var field_for_writemode_publish = "";

	if (EE.publish.show_write_mode === true) { 
		$("#write_mode_textarea").markItUp(myWritemodeSettings);		
	}
	
	if (EE.publish.markitup.fields !== undefined)
	{
		$.each(EE.publish.markitup.fields, function(key, value) { 
			$("#"+key).markItUp(mySettings);
		});	
	}
	
	
	// the height of this window depends on the height of the viewport.	 Percentages dont work
	// as the header and footer are absolutely sized.  This is a great compromise.
	write_mode_height = $(window).height() - (33 + 59 + 25); // the height of header + footer + 25px just to be safe
	$("#write_mode_writer").css("height", write_mode_height+"px");
	$("#write_mode_writer textarea").css("height", (write_mode_height - 67 - 17) + "px"); // for formatting buttons + 17px for appearance
	

	var triggers = $(".write_mode_trigger").overlay({

		// Mask to create modal look
		mask: {
			color: '#262626',
			loadSpeed: 200,
			opacity: 0.85
		},
		
		onBeforeLoad: function(evt) {
			var trigger = this.getTrigger()[0],
				textarea = $("#write_mode_textarea");

									
			if (trigger.id.match(/^id_\d+$/)) {
				field_for_writemode_publish = "field_"+trigger.id;
			} else {
				field_for_writemode_publish = trigger.id.replace(/id_/, '');
			}
			
			// put contents from other page into here
			textarea.val( $("#"+field_for_writemode_publish).val() );
			textarea.focus();
		},
		
		top: 'center',
		closeOnClick: false
	});
	
	// set up the "publish to field" buttons
	$(".publish_to_field").click(function() {
		var currentID = "#" + field_for_writemode_publish.replace(/field_/, ''),
			i =  $('.write_mode_trigger').index(currentID);

		$("#"+field_for_writemode_publish).val($("#write_mode_textarea").val());
		triggers.eq(i).overlay().close();
		return false;
	});
	
	
	$(".closeWindowButton").click(function() {
		var currentID = "#" + field_for_writemode_publish.replace(/field_/, '');
		var i =  $('.write_mode_trigger').index(currentID);

		triggers.eq(i).overlay().close();
		return false;
	});
	
	
	// @todo rewrite dependencies and remove
	
	var abort = false;
	
	function magicMarkups(string) {
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

	// Bind the image html buttons
	$.ee_filebrowser.add_trigger(".btn_img a, .file_manipulate", function(file) {
				
		var textarea, replace = '', props = '',
			open = '', close = '';
		
		// A bit of working around various textareas, text inputs, tec
		
		if ($(this).closest("#markItUpWrite_mode_textarea").length) {
			textareaId = "write_mode_textarea";
		}
		else {
			textareaId = $(this).closest(".publish_field").attr("id").replace("hold_field_", "field_id_");
		}
		
		if (textareaId != undefined) {
			textarea = $("#"+textareaId);
			textarea.focus();		
		}
		
		// We also need to allow file insertion into text inputs (vs textareas) but markitup
		// will not accommodate this, so we need to detect if this request is coming from a 
		// markitup button or another field type.
		
		// Fact is - markitup is actually pretty crappy for anything that doesn't specifically
		// use markitup. So currently the image button only works correctly on markitup textareas.

		if ( ! file.is_image)
		{
			props = EE.upload_directories[file.directory].file_properties;
			
			open = EE.upload_directories[file.directory].file_pre_format;
			open += "<a href=\"{filedir_"+file.directory+"}"+file.name+'" '+props+" >";
			
			close = "</a>";
			close += EE.upload_directories[file.directory].file_post_format;
		}
		else
		{
			props = EE.upload_directories[file.directory].properties;
			
			open = EE.upload_directories[file.directory].pre_format;
			close = EE.upload_directories[file.directory].post_format;

			// Include any user additions before or after the image link
			replace = EE.filebrowser.image_tag.replace(/src="(.*)\[!\[Link:!:http:\/\/\]!\](.*)"/, 'src="$1{filedir_'+file.directory+'}'+file.name+'$2"');
			replace = replace.replace(/\/?>$/, file.dimensions+' '+props+' />');
			
			replace = open + replace + close;
		}


		if (textarea.is("textarea"))
		{
			if ( ! textarea.is('.markItUpEditor')) {
				textarea.markItUp(myNobuttonSettings);
				textarea.closest('.markItUpContainer').find('.markItUpHeader').hide();
				textarea.focus();
			}
			
			// Handle images and non-images differently
			if ( ! file.is_image)
			{
				$.markItUp({
					key:"L",
					name:"Link",
					openWith: open,
					closeWith: close,
					placeHolder:file.name
				});
			}
			else
			{
				$.markItUp({
					replaceWith: replace
				});
			}
		}
		else
		{
			textarea.val(function(i, v) {
				v += open + replace + close;
				return magicMarkups(v);
			});
			
		}

		$.ee_filebrowser.reset(); // restores everything to "default" state - also needed below for file fields
	});

	// File fields
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

	// toggle can not be used here, since it may or may not be visible
	// depending on admin customization
	$(".hide_field span").click(function() {
		
		var holder_id = $(this).parent().parent().attr("id"),
			field_id = holder_id.substr(11),
		
			hold_field = $("#hold_field_"+field_id),
			sub_hold_field = $("#sub_hold_field_"+field_id);		
		
		if (sub_hold_field.css("display") == "block") {
			sub_hold_field.slideUp();
			hold_field.find(".ui-resizable-handle").hide();
			hold_field.find(".field_collapse").attr("src", EE.THEME_URL+"images/field_collapse.png");
		}
		else {
			sub_hold_field.slideDown();
			hold_field.find(".ui-resizable-handle").show();
			hold_field.find(".field_collapse").attr("src", EE.THEME_URL+"images/field_expand.png");
		}
		
		// We dont want datepicker getting triggered when a field is collapsed/expanded
		return false;
	});

	$(".close_upload_bar").toggle(
		function() {
			$(this).parent().children(":not(.close_upload_bar)").hide();
			$(this).children("img").attr("src", EE.THEME_URL+"publish_plus.png");
		}, function () {
			$(this).parent().children().show();
			$(this).children("img").attr("src", EE.THEME_URL+"publish_minus.gif");
		}
	);

	$(".ping_toggle_all").toggle(
		function(){
			$("input.ping_toggle").each(function() {
				this.checked = false;
			});
		}, function (){
			$("input.ping_toggle").each(function() {
				this.checked = true;
			});
		}
	);
	
	if (EE.user.can_edit_html_buttons) {
		$(".markItUp ul").append("<li class=\"btn_plus\"><a title=\""+EE.lang.add_new_html_button+"\" href=\""+EE.BASE+"&C=myaccount&M=html_buttons&id="+EE.user_id+"\">+</a></li>");
	
		$(".btn_plus a").click(function(){
			return confirm(EE.lang.confirm_exit, "");
		});
	}

	// inject the collapse button into the formatting buttons list
	$(".markItUpHeader ul").prepend("<li class=\"close_formatting_buttons\"><a href=\"#\"><img width=\"10\" height=\"10\" src=\""+EE.THEME_URL+"images/publish_minus.gif\" alt=\"Close Formatting Buttons\"/></a></li>");
	
	$(".close_formatting_buttons a").toggle(
		function() {
			$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();
			$(this).parent().parent().css("height", "13px");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_plus.png");
		}, function () {
			$(this).parent().parent().children().show();
			$(this).parent().parent().css("height", "22px");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_minus.gif");
		}
	);

	// Apply a class to its companion tab fitting of its position
	$(".tab_menu li:first").addClass("current");
	
	if (EE.publish.title_focus == true) {
		$("#title").focus();
	}
	
	if (EE.publish.which == 'new') { 
		$("#title").bind("keyup blur", liveUrlTitle);	
	}
	
	if (EE.publish.versioning_enabled == 'n') { 
		$("#revision_button").hide();
	} else {
		$("#versioning_enabled").click(function() {
			if($(this).attr("checked")) {
				$("#revision_button").show(); 
			} else {
				$("#revision_button").hide(); 
			}  
		});
	}
	
	EE.publish.category_editor();
});