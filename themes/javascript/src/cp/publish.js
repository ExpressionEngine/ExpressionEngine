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
		refresh_cats, setup_page, reload, i,
		selected_cats = {},
		$editor_container = $('<div />');

	// categories with a lot of custom fields need to scroll
	cat_modal_container.css({
		height: '100%',
		padding: '0 20px 0 0',	// account for vert scrollbar
		overflow: 'auto'
	});

	// IE caches $.load requests, so we need a unique number
	function now() {
		return +new Date();
	}
	
	cat_modal.dialog({
		autoOpen: false,
		height: 475,
		width: 600,
		modal: true,
		resizable: false,
		title: EE.publish.lang.edit_category,
		open: function(event, ui) {
			$('.ui-dialog-content').css('overflow', 'hidden');
			$('.ui-dialog-titlebar').focus(); // doing this first to fix IE7 scrolling past the dialog's close button
			$('#cat_name').focus();	
			
			// Create listener for file field
			EE.publish.file_browser.category_edit_modal();
		}
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

		if (response.charAt(0) !== '<' && require_valid_response) {
			return refresh_cats(gid);
		}
		
		container.closest(".cat_group_container").find("#refresh_categories").show();
		
		var res = $(response),
			form = res.find("form"),
			submit_button, container_form,
			$category_name, $category_url_title;
				
		if (form.length) {
			cat_modal_container.html(res);
			
			submit_button = cat_modal_container.find("input[type=submit]");
			container_form = cat_modal_container.find("form");
			$category_name = container_form.find('#cat_name');
			$category_url_title = container_form.find('#cat_url_title');
			
			$category_name.keyup(function(event) {
				$category_name.ee_url_title($category_url_title);
			});
			
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
						$editor_container.html(EE.lang.loading);
					},
					success: function(res) {
						res = $.trim(res);
						cat_modal.dialog("close");
						
						if (res[0] == '<') {
							var response = $(res).find(".pageContents"),
								form = response.find("form");

							if (form.length == 0) {
								$editor_container.html(response);
							}
							
							response = response.wrap('<div />').parent(); // outer html hack
							setup_page.call(container, response.html(), true);
						}
						else {
							setup_page.call(container, res, true);
						}
					},
					error: function(res) {
						res = $.parseJSON(res.responseText);
						// cat_modal.dialog("close");
						cat_modal.html(res.error);
						// setup_page.call(container, res.error, true);
					}
				});
				
				return false;
			};
			
			container_form.submit(handle_submit);
			
			var buttons = {};
			buttons[submit_button.remove().attr('value')] = {
				text: EE.publish.lang.update,
				click: function() {
					handle_submit(container_form);
				}
			};
			
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
	reload = function(event) {
		event.preventDefault();

		var link = $(this).hide(),
			gid = $(this).data("gid"),
			resp_filter = ".pageContents";
		
		if ($(this).hasClass("edit_cat_order_trigger") || $(this).hasClass("edit_categories_link")) {
			resp_filter += " table";
		}

		if ( ! gid) {
			gid = $(this).closest(".cat_group_container").data("gid");
		}
		
		// Grab selection if checkboxes are available
		if ($(this).hasClass("edit_categories_link"))
		{
			selected_cats[gid] = cat_groups_containers[gid].find('input:checked').map(function() {
				return this.value;
			}).toArray();
		}
		
		// Hide the checkboxes instead of destroying them in case publish form is
		// submitted while the category editor is still showing
		cat_groups_containers[gid].find('label').hide();
		
		cat_groups_containers[gid].append($editor_container.html(EE.lang.loading));
		
		$.ajax({
			url: $(this).attr('href') + "&timestamp="+now() + resp_filter,
			dataType: "html",
			success: function(response) {
				var res,
					filtered_res = '';
			
				response = $.trim(response);
				
				if (response.charAt(0) == '<') {
					res = $(response).find(resp_filter);
					
					filtered_res = $('<div />').append(res).html();
					if (res.find('form').length == 0) {
						$editor_container.html(filtered_res);
					}
				}

				setup_page.call(cat_groups_containers[gid], filtered_res, true);
			},
			error: function(response) {
				response = $.parseJSON(response.responseText);
				$loading.text(response.error);
				setup_page.call(cat_groups_containers[gid], response.error, true);
			}
		});
	};

	// Hijack edit category links to get it off the ground
	$(".edit_categories_link").click(reload);
	
	// Hijack internal links (except for done and adding filename)
	$('.cat_group_container a:not(.cats_done, .choose_file)').live('click', reload);
	
	// Last but not least - update the checkboxes
	$(".cats_done").live("click", function() {
		var that = $(this).closest(".cat_group_container"),
			gid = that.data("gid");

		$(".edit_categories_link").each(function(el, i) {
			if ($(this).data("gid") == gid) {
				$(this).show();
			}
		});
		
		that.text("loading...").load(EE.BASE+"&C=content_publish&M=category_actions&group_id="+that.data("gid")+"&timestamp="+now(), function(response) {
			that.html( $(response).html() );
			
			$.each(selected_cats[gid], function(k, v) {
				that.find('input[value='+v+']').attr('checked', 'checked');
			});
		});
				
		return false;
	});
};

/**
 * Get the percentage width of a field
 *
 * @param {jQuery Object} $element The jQuery object of a field in the publish form (e.g. $('.publish_field'))
 * @returns The percentage width of the field
 * @type Number
 */
EE.publish.get_percentage_width = function($element) {
	var width = 0,
		isInteger = /[0-9]+/ig,
		dataWidth = $element.attr('data-width');
	
	if (dataWidth && isInteger.test(dataWidth.slice(0, -1))) {
		return parseInt(dataWidth, 10);
	};
	
	return Math.round(($element.width() / $element.parent().width()) * 10) * 10;
};


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
					percent_width = EE.publish.get_percentage_width(that),
					temp_buttons = $("#sub_hold_field_"+id+" .markItUp ul li:eq(2)"),
					layout_settings;
				
				if (percent_width > 100) {
					percent_width = 100;
				};
					
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
			data: "XID="+EE.XID+"&json_tab_layout="+encodeURIComponent(JSON.stringify(layout_object))+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,
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
			return true;
		}
	});
	
	return false;
};

/**
 * Change the Preview Layout link to use the member group's ID
 * Hits the preview_layout method to create a message
 */
EE.publish.change_preview_link = function() {
	$select = $('#layout_preview select');
	$link = $('#layout_group_preview');
	base = $link.attr('href').split('layout_preview')[0];
	$link.attr('href', base + 'layout_preview=' + $select.val());
	
	$.ajax({
		url: EE.BASE + "&C=content_publish&M=preview_layout",
		type: 'POST',
		dataType: 'json',
		data: {
			XID: EE.XID,
			member_group: $select.find('option:selected').text()
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
		
		date_obj_am_pm = (date_obj_hours < 12) ? ' AM': ' PM';

		// This turns midnight into 12 AM, so ignore if it's already 0
		if (date_obj_hours != 0) {
		    date_obj_hours = ((date_obj_hours + 11) % 12) + 1;
		}
	}
	
	if (date_obj_hours < 10) {
		date_obj_hours = "0" + date_obj_hours;
	}
	
	return " '" + date_obj_hours + ":" + date_obj_mins + date_obj_am_pm + "'";
}());


file_manager_context = "";	// @todo - yuck, should be on the EE global


function disable_fields(state) {
	
	var fields = $(".main_tab input, .main_tab textarea, .main_tab select, #submit_button"),
		submit = $("#submit_button"),
		admin_link = $("#holder").find('a');
		
	if (state) {
		disabled_fields = fields.filter(':disabled');
		fields.attr("disabled", true);
		submit.addClass("disabled_field");
		admin_link.addClass("admin_mode");
		$("#holder div.markItUp, #holder p.spellcheck").each(function() {
			$(this).before("<div class=\"cover\" style=\"position:absolute;width:98%;height:50px;z-index:9999;\"></div>").css({});
		});

		$('.contents, .publish_field input, .publish_field textarea').css('-webkit-user-select', 'none');
	}
	else {
		fields.removeAttr("disabled");
		submit.removeClass("disabled_field");
		admin_link.removeClass("admin_mode");
		$(".cover").remove();
		disabled_fields.attr("disabled", true);

		$('.contents, .publish_field input, .publish_field textarea').css('-webkit-user-select', 'auto');
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
	
	$('#layout_preview select').change(function(){
		EE.publish.change_preview_link();
	});

	$("a.reveal_formatting_buttons").click(function(){
		$(this).parent().parent().children('.close_container').slideDown();
		$(this).hide();
		return false;
	});

	$("#write_mode_header .reveal_formatting_buttons").hide();

	$("a.glossary_link").click(function(){
		$(this).parent().siblings('.glossary_content').slideToggle("fast");$(this).parent().siblings('.smileyContent .spellcheck_content').hide();
		return false;
	});

	if (EE.publish.smileys === true) {
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

	if (EE.publish.autosave && EE.publish.autosave.interval) {
		
		var autosaving = false;
		
		start_autosave = function() {
			if (autosaving) {
				return;
			}
			
			autosaving = true;
			setTimeout(autosave_entry, 1000 * EE.publish.autosave.interval); // 1000 milliseconds per second
		};
		
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

		if ( ! pagesUri.val()) {
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

	
	if (EE.publish.markitup.fields !== undefined)
	{
		$.each(EE.publish.markitup.fields, function(key, value) { 
			$("#"+key).markItUp(mySettings);
		});	
	}
	
	EE.publish.setup_writemode = function() {
		var wm_inner = $('#write_mode_writer'),
			wm_txt = $('#write_mode_textarea'),
			source_sel, wm_sel,
			source_txt, triggers;
		
		wm_txt.markItUp(myWritemodeSettings);
		
		// the height of this modal depends on the height of the viewport
		// we'll dynamically resize as a user may want proper fullscreen
		// by changing their browser window once it's open.
		
		$(window).resize(function() {
			var wm_height = $(this).height() - (33 + 59 + 25);	// header + footer + 25px just to be safe
			
			wm_inner
				.css("height", wm_height + "px")
				.find("textarea").css("height", (wm_height - 67 - 17) + "px");	// for formatting buttons + 17px for appearance
			
		}).triggerHandler('resize');
		
		$(".write_mode_trigger").overlay({

			// only exit by clicking an action
			closeOnEsc: false,
			closeOnClick: false,
			
			top: 'center',
			target: '#write_mode_container',

			// Mask to create modal look
			mask: {
				color: '#262626',
				loadSpeed: 200,
				opacity: 0.85
			},

			// Event handlers
			onBeforeLoad: function(evt) {
				var trigger_id = this.getTrigger()[0].id;

				// regular field (id_#) or named field (id_forum_content_blah)
				if (trigger_id.match(/^id_\d+$/)) {
					source_txt = $("#field_" + trigger_id);
				} else {
					source_txt = $('#' + trigger_id.replace(/id_/, ''));
				}
				
				source_sel = source_txt.getSelectedRange();
				wm_txt.val( source_txt.val() );
			},
			
			onLoad: function(evt) {
				// recreate the old cursor position
				wm_txt.focus();
				wm_txt.createSelection(source_sel.start, source_sel.end);
				
 				// monkey patching the closers so that we can
				// standardize srcElement for all browsers
				// (looking at you FireFox)
				var that = this;
				that.getClosers().unbind('click').click(function(e) {
					e.srcElement = this;
					that.close(e);
				});
			},
			
			onBeforeClose: function(evt) {
				var closer = $(evt.srcElement).closest('.close'),	// evt.target is overriden by the custom event trigger =(
					isSave = closer.hasClass('publish_to_field');

				if (closer.hasClass('publish_to_field')) {
					wm_sel = wm_txt.getSelectedRange();
					source_txt.val( wm_txt.val() );
					source_txt.createSelection(wm_sel.start, wm_sel.end);
				}

				source_txt.focus();
			}
		});
	}
	
	
	if (EE.publish.show_write_mode === true) { 
		EE.publish.setup_writemode();
	}

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
			$(this).parent().parent().css("height", "auto");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_minus.gif");
		}
	);

	// Apply a class to its companion tab fitting of its position
	$(".tab_menu li:first").addClass("current");
	
	if (EE.publish.title_focus == true) {
		$("#title").focus();
	}
	
	if (EE.publish.which == 'new') { 
		$("#title").bind("keyup blur", function() {
			$('#title').ee_url_title($('#url_title'));
		});	
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
	
	// @todo if admin bridge
	if (EE.publish.hidden_fields) {
		EE._hidden_fields = [];
		
		var inputs = $("input");
					
		$.each(EE.publish.hidden_fields, function(k) {
			EE._hidden_fields.push(inputs.filter("[name="+k+"]")[0]);
		});
		
		$(EE._hidden_fields).after('<p class="hidden_blurb">This module field only shows in certain circumstances. This is a placeholder to let you define it in your layout.</p>');
	}
	
});
