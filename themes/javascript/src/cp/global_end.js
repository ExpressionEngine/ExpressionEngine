/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console */

"use strict";

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

/**
 * This file always runs dead last.
 *
 * We use it to initialize optional modules
 * that are loaded by our libraries. For example,
 * the table library loads up the table plugin in
 * a datasource is used.
 *
 * That plugin is ultimately bound here.
 */

// ------------------------------------------------------------------------


// Apply ee_table and ee_toggle_all to any tables that want it
$('table').each(function() {
	var config;

	if ($(this).data('table_config')) {
		config = $(this).data('table_config');
		$(this).table(config);
	}
	
	// Apply ee_toggle_all only if it's loaded
	if (jQuery().toggle_all)
	{
		$(this).toggle_all();
	}
});





// Start the logout checks
// @todo This is really nasty and a partial duplicate of the
// global EE.cp.logout_confirm. Fix it!

$(function() {

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

	// This is largely ripped off from pascal in global_start. -- greg
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
	
});