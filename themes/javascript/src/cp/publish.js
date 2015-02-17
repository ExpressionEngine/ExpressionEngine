/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, devel: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE */

"use strict";

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

	$("#tab_menu_tabs a:not(.add_tab_link)").each(function() {

		// skip list items with no id (ie: new tab)
		if ($(this).parent('li').attr('id') && $(this).parent('li').attr('id').substring(0,5) == "menu_")
		{
			var tab_name =  $(this).parent('li').attr('id').substring(5),
				tab_id	 = $(this).parent('li').attr('id').substring(5),
				tab_label = $(this).parent('li').attr('title');
			field_index = 0;
			visible = true;

			if( $(this).parent('li').is(':visible') )
			{
				lay_name = tab_name;
				layout_object[tab_count] = {name: lay_name, fields: {}};
				layout_object[tab_count]['fields'][mypre] = tab_label;
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
					layout_object[tab_count]['fields'][id] = layout_settings;
					field_index += 1;
				} else {
					layout_hidden[id] = layout_settings;
				}

			});

			if (visible === true) {
				tab_count++;
			}
		}
	});

	if (merge == true)
	{
		// Add hidden fields to first tab

		var last_index = 0;
		var fields = layout_object[0]['fields'];
		for (i in fields) {
			if (fields[i]['index'] > last_index) {
				last_index = fields[i]['index'];
			}
		}

		// Reindex first tab
		$.each(layout_hidden, function() {
			this['index'] = ++last_index;
		});

		jQuery.extend(layout_object[0]['fields'], layout_hidden);
	}

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
			data: "json_tab_layout="+encodeURIComponent(JSON.stringify(layout_object))+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,
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
		data: "json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id+"&field_group="+EE.publish.field_group,
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
			member_group: $select.find('option:selected').text()
		}
	});

};

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
			parent_div.find('[name='+field_id+']').insertAtCursor( $(this).attr('title') );

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
			$("textarea[name="+key+"]").markItUp(mySettings);
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
					source_txt = $("textarea[name=field_"+trigger_id+"]");
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
		$("#publishForm input[name=title]").focus();
	}

	if (EE.publish.which == 'new') {
		$("#publishForm input[name=title]").bind("keyup blur", function() {
			$('#publishForm input[name=title]').ee_url_title($('#publishForm input[name=url_title]'));
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