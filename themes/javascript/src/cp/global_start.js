/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console */

"use strict";

/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// Setup Base EE Control Panel
jQuery(document).ready(function () {

	var $ = jQuery;

	// Setup Global Ajax Events

	// Ajax Errors can be hard to debug so we'll always add a simple
	// error handler if none were specified.
	$.ajaxPrefilter(function(options, originalOptions, jqXHR) {

		if ( ! _.has(options, 'error'))
		{
			jqXHR.error(function(data) {
				_.defer(function() {
					throw [data.statusText, data.responseText];
				});
			});
		}
	});

	// A 401 in combination with a url indicates a redirect, we use this
	// on the login page to catch periodic ajax requests (e.g. autosave)

	$(document).bind('ajaxComplete', function (evt, xhr) {
		if (xhr.status && (+ xhr.status) === 401) {
			window.location = EE.BASE + '&' + xhr.responseText;
		}
	});

	// call the input placeholder polyfill early so that we don't get
	// weird flashes of content
	if ( ! 'placeholder' in document.createElement('input')) 
	{
		EE.insert_placeholders();
	}

	
	// External links open in new window

	$('a[rel="external"]').click(function () {
		window.open(this.href);
		return false;
	});

	EE.cp.zebra_tables();
	EE.cp.show_hide_sidebar();
	EE.cp.display_notices();
	EE.cp.deprecation_meaning();
	

	// Setup Notepad
	EE.notepad = (function () {
	
		var notepad = $('#notePad'),
			notepad_form = $("#notepad_form"),
			notepad_txtarea = $('#notePadTextEdit'),
			notepad_controls = $('#notePadControls'),
			notepad_text = $('#notePadText');
			notepad_empty = notepad_text.text(),
			current_content = notepad_txtarea.val();
	
		return {
			init: function () {
				if (current_content) {
					notepad_text.html(current_content.replace(/</ig, '&lt;').replace(/>/ig, '&gt;').replace(/\n/ig, '<br />'));
				}
			
				notepad.click(EE.notepad.show);
				notepad_controls.find('a.cancel').click(EE.notepad.hide);
			
				notepad_form.submit(EE.notepad.submit);
				notepad_controls.find('input.submit').click(EE.notepad.submit);
			
				notepad_txtarea.autoResize();
			},
		
			submit: function () {
				current_content = $.trim(notepad_txtarea.val());

				var newval = current_content.replace(/</ig, '&lt;').replace(/>/ig, '&gt;').replace(/\n/ig, '<br />');

				notepad_txtarea.attr('readonly', 'readonly').css('opacity', 0.5);
				notepad_controls.find('#notePadSaveIndicator').show();

				$.post(notepad_form.attr('action'), {'notepad': current_content, 'XID': EE.XID }, function (ret) {
					notepad_text.html(newval || notepad_empty).show();
					notepad_txtarea.attr('readonly', false).css('opacity', 1).hide();
					notepad_controls.hide().find('#notePadSaveIndicator').hide();
				}, 'json');
				return false;
			},
		
			show: function () {
				// Already showing?
				if (notepad_controls.is(':visible')) {
					return false;
				}

				var newval = '';

				if (notepad_text.hide().text() !== notepad_empty) {
					newval = notepad_text.html().replace(/<br>/ig, '\n').replace(/&lt;/ig, '<').replace(/&gt;/ig, '>');
				}

				notepad_controls.show();
				notepad_txtarea.val(newval).show()
								.height(0).focus()
								.trigger('keypress');
			},
		
			hide: function () {
				notepad_text.show();
				notepad_txtarea.hide();
				notepad_controls.hide();
				return false;
			}
		};
	}());

	EE.notepad.init();
	
	EE.cp.accessory_toggle();

	EE.cp.control_panel_search();

	// Setup sidebar hover descriptions

	$('h4', '#quickLinks').click(function () {
		window.location.href = EE.BASE + '&C=myaccount&M=quicklinks';
	})
	.add('#notePad').hover(function () {
		$('.sidebar_hover_desc', this).show();
	}, function () {
		$('.sidebar_hover_desc', this).hide();
	})
	.css('cursor', 'pointer');

	EE.cp.logout_confirm();	
}); // ready

/**
 * Namespace function that non-destructively creates "namespace" objects (e.g. EE.publish.example)
 * @param {String} namespace_string The namespace string (e.g. EE.publish.example)
 * @returns The object to create
 */
EE.namespace = function(namespace_string) 
{
	var parts = namespace_string.split('.'),
		parent = EE;
	
	// strip redundant leading global 
	if (parts[0] === "EE")
	{
		parts = parts.slice(1);
	}
	
	// @todo disallow 'prototype', duh
	// create a property if it doesn't exist if (typeof parent[parts[i]] === "undefined") {
	for (var i = 0, max = parts.length; i < max; i += 1) 
	{
		if (typeof parent[parts[i]] === "undefined") {
			parent[parts[i]] = {};
		};
		
		parent = parent[parts[i]];
	}
	
	return parent;
};

EE.namespace('EE.cp');

// Show / hide accessories
EE.cp.accessory_toggle = function() {
	$('#accessoryTabs li a').click(function (event) {
		event.preventDefault();
		
		var $parent = $(this).parent("li"),
			$accessory = $("#" + this.className);

		if ($parent.hasClass("current")) {
			$accessory.slideUp('fast');
			$parent.removeClass("current");
		}
		else 
		{
			if ($parent.siblings().hasClass("current")) {
				$accessory.show().siblings(":not(#accessoryTabs)").hide();
				$parent.siblings().removeClass("current");
			}
			else {
				$accessory.slideDown('fast');
			}
			$parent.addClass("current");
		}
	});
};

// Ajax for control panel search
EE.cp.control_panel_search = function() {
	var search = $('#search'),
		result = search.clone(),
		buttonImgs = $('#cp_search_form').find('.searchButton'),
		submit_handler;

	submit_handler = function () {
		var url = $(this).attr('action'),
			data = {
				'cp_search_keywords': $('#cp_search_keywords').val(),
				'XID': EE.XID
			};

		$.ajax({
			url: url,
			data: data,
			type: 'POST',
			dataType: 'html',
			beforeSend: function () {
				buttonImgs.toggle();
			},
			success: function (ret) {
				buttonImgs.toggle();
				search = search.replaceWith(result);
				result.html(ret);

				$('#cp_reset_search').click(function () {
					result = result.replaceWith(search);

					$('#cp_search_form').submit(submit_handler);
					$('#cp_search_keywords').select();
					return false;
				});
			}
		});

		return false;
	};

	$('#cp_search_form').submit(submit_handler);
};

// Hook up show / hide actions for sidebar
EE.cp.show_hide_sidebar = function() {
	var w = {'revealSidebarLink': '77%', 'hideSidebarLink': '100%'},
		main_content = $("#mainContent"),
		sidebar = $("#sidebarContent"),
		main_height = main_content.height(),
		sidebar_height = sidebar.height(),
		larger_height;
	
	// Sidebar state

	if (EE.CP_SIDEBAR_STATE === "n") {
		main_content.css("width", "100%");
		$("#revealSidebarLink").css('display', 'block');
		$("#hideSidebarLink").hide();
	
		sidebar.show();
		sidebar_height = sidebar.height();
		sidebar.hide();
	}
	else {
		sidebar.hide();
		main_height = main_content.height();
		sidebar.show();
	}

	larger_height = sidebar_height > main_height ? sidebar_height : main_height;
	
	$('#revealSidebarLink, #hideSidebarLink').click(function () {
		var that = $(this),
			other = that.siblings('a'),
			show = (this.id === 'revealSidebarLink');		

		$.ajax({
			type: "POST",
			dataType: 'json',
			url: EE.BASE + '&C=myaccount&M=update_sidebar_status',
			data: {'XID' : EE.XID, 'show' : show},
			success: function(result){
				if (result.messageType === 'success') {
					// log?
				}
			}
		});	

		$("#sideBar").css({
			'position': 'absolute',
			'float': '',
			'right': '0'
		});
	
	
		that.hide();
		other.css('display', 'block');
	
		sidebar.slideToggle();
		main_content.animate({
			"width": w[this.id],
			"height": show ? larger_height : main_height
		}, function () {
			main_content.height('');
			$("#sideBar").css({
				'position': '',
				'float': 'right'
			});
		});
		
		return false;
	});
};

// Move notices to notification bar for consistency
EE.cp.display_notices = function() {

	var types = ["success", "notice", "error"]; 

	$(".message.js_hide").each(function() {
		for (i in types) {
			if ($(this).hasClass(types[i])) {
				$.ee_notice($(this).html(), {type: types[i]});
			}
		}
	});
};

// Fallback for browsers without placeholder= support
EE.insert_placeholders = function () {

	$('input[type="text"]').each(function() {
		if ( ! this.placeholder) {
			return;
		}
						
		var jqEl = $(this),
			placeholder = this.placeholder,
			orig_color = jqEl.css('color');

		if (jqEl.val() == '') {
			jqEl.data('user_data', 'n');
		}

		jqEl.focus(function () {
			// Reset color & remove placeholder text
			jqEl.css('color', orig_color);
			if (jqEl.val() === placeholder) {
				jqEl.val('');
				jqEl.data('user_data', 'y');
			}
		})
		.blur(function () {
			// If no user content -> add placeholder text and dim
			if (jqEl.val() === '' || jqEl.val === placeholder) {
				jqEl.val(placeholder).css('color', '#888');
				jqEl.data('user_data', 'n');
			}
		})
		.trigger('blur');
	});
};

// Logout button confirmation
EE.cp.logout_confirm = function() {
	$("#activeUser").one("mouseover", function () {

		var logout_modal = $('<div id="logOutConfirm">' + EE.lang.logout_confirm + ' </div>'),
			ttl = 30,
			orig_ttl = ttl,
			countdown_timer, buttons,
			log_me_out, delay_logout;

		log_me_out = function () {
			// Won't redirect on unload
			$.ajax({
				url: EE.BASE + "&C=login&M=logout",
				async: (! $.browser.safari)
			});

			// Redirect
			window.location = EE.BASE + "&C=login&M=logout";
		};

		delay_logout = function () {
			if (ttl < 1) {
				return setTimeout(log_me_out, 0);
			}
			else if (ttl === orig_ttl) {
				$(window).bind("unload.logout", log_me_out);
			}

			logout_modal.dialog("option", "title", EE.lang.logout + " (" +  (ttl-- || "...")  + ")");
			countdown_timer = setTimeout(delay_logout, 1000);
		};

		function cancel_logout() {
			clearTimeout(countdown_timer);
			$(window).unbind("unload.logout");
			ttl = orig_ttl;
		}

		buttons = { 
			Cancel: function () { 
				$(this).dialog("close"); 
			}
		};

		buttons[EE.lang.logout] = log_me_out;

		logout_modal.dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			title: EE.lang.logout,
			position: "center",
			minHeight: "0",
			buttons: buttons,
			beforeClose: cancel_logout
		});

		$("a.logOutButton", this).click(function () {
			$("#logOutConfirm").dialog("open");
			$(".ui-dialog-buttonpane button:eq(2)").focus(); //focus on Log-out so pressing return logs out

			delay_logout();
			return false;
		});
	});
};

// Modal for "What does this mean?" link on deprecation notices
EE.cp.deprecation_meaning = function()
{
	$('.deprecation_meaning').click(function(event)
	{
		event.preventDefault();
		
		var deprecation_meaning_modal = $('<div class="alert">' + EE.developer_log.deprecation_meaning + ' </div>');
		
		deprecation_meaning_modal.dialog({
			height: 300,
			modal: true,
			title: EE.developer_log.dev_log_help,
			width: 460
		});
	});
};

EE.cp.zebra_tables = function(table) {
	table = table || $('table');
	
	if ( ! table.jquery) {
		table = $(table);
	}
		
	$(table)
		.find('tr')
		.removeClass('even odd')
		.filter(':even').addClass('even')
		.end()
		.filter(':odd').addClass('odd');
};



// First step in deprecating scripts in add_to_head().
// Next release the message will be more visible/annoying.

(function() {
	var SCRIPT_COUNT = 2, // global_js, jquery
		scripts = $('head script');

	// anything but jquery and global_js shouldn't be there.
	if (scripts.length > SCRIPT_COUNT) {

		console.groupCollapsed('Found third party scripts in <head> tag.');
		console.log('Please use cp->add_to_foot() to add scripts. jQuery and the EE global will be moved down in a future release.');

		scripts.slice(SCRIPT_COUNT).each(function() {
			console.log(this.src && this.src || '[Inline Script]');
		});

		console.groupEnd();
	}
})();