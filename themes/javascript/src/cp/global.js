/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console */

"use strict";

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

// Setup Base EE Control Panel
jQuery(document).ready(function () {

	var $ = jQuery;

	// Setup Global Ajax Events
	// A 401 in combination with a url indicates a redirect, we use this
	// on the login page to catch periodic ajax requests (e.g. autosave)

	$(document).bind('ajaxComplete', function (evt, xhr) {
		if (xhr.status && (+ xhr.status) === 401) {
			window.location = EE.BASE + '&' + xhr.responseText;
		}
	});

	if ( ! 'placeholder' in document.createElement('input')) 
	{
		EE.insert_placeholders();
	}

	
	// External links open in new window

	$('a[rel="external"]').click(function () {
		window.open(this.href);
		return false;
	});


	function logOutCheck() {
	
	    var timeOutTimer			= EE.SESS_TIMEOUT - 60000, //Fire one Minute before the session times out.  
			xidTimeOutTimer			= EE.XID_TIMEOUT - 60000,
			pageExpirationTimeout	= (timeOutTimer < xidTimeOutTimer) ? timeOutTimer : xidTimeOutTimer,
			loginHit				= false,
			isPageAboutToExpire, xidRefresh;
	
		xidRefresh = function () {
			$.ajax({
				type:		'POST',
				dataType:	'json',
				url:		EE.BASE + '&C=login&M=refresh_xid',
				success: function (result) {
					$("input[name='XID']").val(result.xid);
					EE.XID = result.xid;
					setTimeout(xidRefresh, xidTimeOutTimer);
				}
			});	
		};
	
		isPageAboutToExpire = function () {
			var logInForm = '<form><div id="logOutWarning" style="text-align:center"><p>' + EE.lang.session_expiring + '</p><label for="username">' + EE.lang.username + '</label>: <input type="text" id="log_backin_username" name="username" value="" style="width:100px" size="35" dir="ltr" id="username" maxlength="32"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="password">' + EE.lang.password + '</label>: <input class="field" id="log_backin_password" type="password" name="password" value="" style="width:100px" size="32" dir="ltr" id="password" maxlength="32"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="submit" name="submit" value="' + EE.lang.login + '" class="submit" /><span id="logInSpinner"></span></div></form>',
				logOutWarning;

			if (loginHit === true) {
				finalLogOutTimer(loginHit);
				return false;
			} else {
				setTimeout(finalLogOutTimer, pageExpirationTimeout);
			}

			$.ee_notice(logInForm, {type: "custom", open: true, close_on_click: false});

			logOutWarning = $('#logOutWarning');
			logOutWarning.find('#log_backin_username').focus();
			logOutWarning.find("input#submit").click(function () {

				var username        = logOutWarning.find('input#log_backin_username').val(),
					password        = logOutWarning.find('input#log_backin_password').val(),
					submitBtn       = $(this),
					logInSpinner    = logOutWarning.find('span#logInSpinner');

				submitBtn.hide();
				logInSpinner.html('<img src="' + EE.PATH_CP_GBL_IMG + 'loader_blackbg.gif" />');		

				$.ajax({
					type:		"POST",
					dataType:	'json',
					url:		EE.BASE + "&C=login&M=authenticate&is_ajax=true",
					data:		{'username' : username, 'password' : password, 'XID' : EE.XID},
					success: function (result) {
					
						loginHit = true;
					
						if (result.messageType === 'success') {
							// Regenerate XID
							$("input[name='XID']").val(result.xid);

							logOutWarning.slideUp('fast');
							$.ee_notice(result.message, {type : "custom", open: true});
							
							setTimeout($.ee_notice.destroy, 1600);
						
							EE.XID = result.xid;

							loginHit = true;

							// Reset Timeout
							clearTimeout(isPageAboutToExpire);
							setTimeout(isPageAboutToExpire, pageExpirationTimeout);
					
						} else if (result.messageType === 'failure') {
							logOutWarning.before('<div id="loginCheckFailure">'  +  result.message  +  '</div>');                        
							logInSpinner.hide('fast');
							submitBtn.css('display', 'inline');
						} else if (result.messageType === 'logout') {
							window.location.href = EE.BASE + '&C=login&M=logout&auto_expire=true';
						}
					}
				});
				return false;
			});
		};
		
		if (EE.SESS_TYPE === 'c') {
			setTimeout(xidRefresh, xidTimeOutTimer);
		} else { 
			setTimeout(isPageAboutToExpire, pageExpirationTimeout);
		}
	}

	// This is largely ripped off from pascal below. -- greg
	var finalLogOutTimer = function (loginHit) {

		var logoutModal = $('<div id="logOutConfirm">' + EE.lang.session_timeout + ' </div>'),
			ttl = 30,
			orig_ttl = ttl,
			logoutCountdown, buttons,
			logOut, delayLogout;
	
		logOut = function () {
			window.location = EE.BASE + "&C=login&M=logout&auto_expire=true";
		};

		delayLogout = function () {
			if (ttl < 1) {
				return setTimeout(logOut, 0);
			}
			else if (ttl === orig_ttl) {
				$(window).bind("unload.logout", logOut);
			}

			logoutModal.dialog("option", "title", EE.lang.logout + " (" +  (ttl-- || "...")  + ")");
			logoutCountdown = setTimeout(delayLogout, 1000);
		};

		function cancelLogout() {
			clearTimeout(logoutCountdown);
			$(window).unbind("unload.logout");
			ttl = orig_ttl;
		
			$.ajax({
				type:		'POST',
				dataType:	'json',
				url:		EE.BASE + '&C=login&M=refresh_xid',
				success: function (result) {
					$("input[name='XID']").val(result.xid);
					EE.XID = result.xid;
					$('#logOutWarning').slideUp('fast');
					logOutCheck();
				}
			});
			loginHit = false;
		}

		buttons = { 
			Cancel: function () { 
				$(this).dialog("close"); 
			}
		};
		
		buttons[EE.lang.logout] = logOut;

		logoutModal.dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			title: EE.lang.logout,
			position: "center",
			minHeight: "0",
			buttons: buttons,
			beforeClose: cancelLogout
		});

		$("#logOutConfirm").dialog("open");
		$(".ui-dialog-buttonpane button:eq(2)").focus(); //focus on Log-out so pressing return logs out

		delayLogout();
		return false;
	};

	if (EE.SESS_TIMEOUT) {
		logOutCheck();	
	}
		
	EE.cp.show_hide_sidebar();


//	if (EE.flashdata !== undefined) {
		EE.cp.display_notices();
//	}

	// Setup Notepad
	EE.notepad = (function () {
	
		var notepad = $('#notePad'),
			notepad_form = $("#notepad_form"),
			notepad_txtarea = $('#notePadTextEdit'),
			notepad_controls = $('#notePadControls'),
			notepad_text = $('#notePadText').removeClass('js_show'),	// .show() was really slow on this - not sure why
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

	$(".js_show").show();
	
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