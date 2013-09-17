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
		var old_xid = EE.XID,
			type = options.type.toUpperCase();

		// Throw all errors
		if ( ! _.has(options, 'error')) {
			jqXHR.error(function(data) {
				_.defer(function() {
					throw [data.statusText, data.responseText];
				});
			});
		}

		// Add XID to EE POST requests
		if (type == 'POST' && options.crossDomain === false) {
			jqXHR.setRequestHeader("X-EEXID", old_xid);
		}

		var defaultHeaderResponses = {
			// Refresh xids
			xid: function(new_xid) {
				EE.XID = new_xid;
				$('input[name="XID"]').filter('[value="'+old_xid+'"]').val(new_xid);
			},

			// Force redirects (e.g. logout)
			redirect: function(url) {
				window.location = EE.BASE + '&' + url.replace('//', '/'); // replace to prevent //example.com
			},

			broadcast: function(event) {
				EE.cp.broadcastEvents[event]();
				$(window).trigger('broadcast', event);
			}
		};

		// Set EE response header defaults
		eeResponseHeaders = $.merge(
			defaultHeaderResponses,
			originalOptions.eeResponseHeaders || {}
		);

		jqXHR.complete(function(xhr) {

			if (options.crossDomain === false) {
				_.each(eeResponseHeaders, function(callback, name) {
					var headerValue = xhr.getResponseHeader('X-EE'+name);

					if (headerValue) {
						callback(headerValue);
					}
				});
			}
		});
	});

	// A 401 in combination with a url indicates a redirect, we use this
	// on the login page to catch periodic ajax requests (e.g. autosave)

	// Deprecated! Use X-EERedirect
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

	// Notice banners
	if (EE.importantMessage) {
		msgBoxOpen = EE.importantMessage.state;
		msgContainer = $("#ee_important_message");

		save_state = function () {
			msgBoxOpen = ! msgBoxOpen;
			document.cookie = "exp_home_msg_state=" + (msgBoxOpen ? "open" : "closed");
		};

		setup_hidden = function () {
			$.ee_notice.show_info(function () {
				$.ee_notice.hide_info();
				msgContainer.removeClass("closed").show();
				save_state();
			});
		};

		msgContainer.find(".msg_open_close").click(function () {
			msgContainer.hide();
			setup_hidden();
			save_state();
		});

		if (! msgBoxOpen) {
			setup_hidden();
		}
	}

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

				$.post(notepad_form.attr('action'), {'notepad': current_content }, function (ret) {
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
}); // ready

/**
 * Namespace function that non-destructively creates "namespace" objects (e.g. EE.publish.example)
 * @param {String} namespace_string The namespace string (e.g. EE.publish.example)
 * @returns The object to create
 */
EE.namespace = function(namespace_string) {
	var parts = namespace_string.split('.'),
		parent = EE;

	// strip redundant leading global
	if (parts[0] === "EE") {
		parts = parts.slice(1);
	}

	// @todo disallow 'prototype', duh
	// create a property if it doesn't exist if (typeof parent[parts[i]] === "undefined") {
	for (var i = 0, max = parts.length; i < max; i += 1) {
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
				'cp_search_keywords': $('#cp_search_keywords').val()
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

	$('#revealSidebarLink, #hideSidebarLink').click(function (evt) {
		var that = $(this),
			other = that.siblings('a'),
			show = (this.id === 'revealSidebarLink');

		$.ajax({
			type: "POST",
			dataType: 'json',
			url: EE.BASE + '&C=myaccount&M=update_sidebar_status',
			data: {'show' : show},
			success: function(result) {
				if (result.messageType === 'success') {
					// log?
				}
			}
		});

		if ( ! evt.isTrigger) {
			$(window).trigger('broadcast.sidebar', show);
		}

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

	$(window).bind('broadcast.sidebar', function(event, sidebarIsOpen) {
		var selectors = {
			true: "#revealSidebarLink",
			false: "#hideSidebarLink"
		};

		$(selectors[sidebarIsOpen]).filter(':visible').trigger('click');
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


EE.cp.broadcastEvents = (function() {

	/**
	 * Handle idle / inaction between windows
	 *
	 * This code relies heavily on timing. In order to reduce complexity everything is
	 * handled in steps (ticks) of 15 seconds. We count for how many ticks we have been
	 * in a given state and act accordingly. This gives us reasonable timing information
	 * without having to set, cancel, and track multiple timeouts.
	 *
	 * The conditions currently are as follows:
	 *
	 * - If an ee tab has focus we call it idle after 20 minutes of no interaction
	 * - If no ee tab has focus, we call it idle after 40 minutes of no activity
	 * - If the modal receive no interaction for more than 30 minutes, we show the login page.
	 * - If they work around the modal (inspector), all request will land on the login page.
	 * - Logging out of one tab will show the modal on all other tabs.
	 * - Logging into the modal on one tab, will show it on all other tabs.
	 */
		// - Config toggle to turn it off?

	// Define our time limits:
	var TICK_TIME          = 15 * 1000, // 15 seconds between ticks, 4 per minute
		FOCUSED_TICK_LIMIT = 4 * 20,    // 20 minutes: time before modal if window focused
		BLURRED_TICK_LIMIT = 4 * 40,    // 40 minutes: time before modal if no focus
		LOGOUT_TICK_LIMIT  = 4 * 30;    // 30 minutes: time before logout if no modal interaction

	// Make sure we have our modal available when we need it
	var logoutModal = $('#idle-modal').dialog({
		autoOpen: false,
		resizable: false,
		title: EE.lang.session_idle,
		modal: true,
		closeOnEscape: false,
		position: "center",
		height: 'auto',
		width: 354
	});

	// This modal is required, remove the close button in the titlebar.
	logoutModal.closest('.ui-dialog').find('.ui-dialog-titlebar-close').remove();

	// Bind on the modal submission
	logoutModal.find('form').on('submit', function() {

		var oldBase = EE.BASE;

		$.ajax({
			type: 'POST',
			url: this.action,
			data: $(this).serialize(),
			dataType: 'json',

			success: function(result) {
				if (result.messageType != 'success') {
					alert(result.message);
					return;
				}

				// Hide the dialog
				logoutModal.off('dialogbeforeclose');
				logoutModal.dialog('close');

				// Fix the EE.BASE variable
				EE.BASE = result.base.replace(/&amp;/g, '&');

				var replaceBase = function(i, value) {
					return value.replace(oldBase, EE.BASE);
				};

				$('a').prop('href', replaceBase);
				$('form').prop('action', replaceBase);

				$(window).trigger('broadcast.idleState', 'login');
			},

			error: function(data) {
				alert(data.message);
			}
		});

		return false;
	});

	/**
	 * This object tracks the current state of the page.
	 *
	 * The resolve function is called once per tick. The individual events will
	 * set hasFocus and increment idleCount.
	 */
	var State = {

		hasFocus: true,
		modalActive: false,
		idleCount: 0,
		pingReceived: false,

		modalThresholdReached: function() {
			var mustShowModal = (this.hasFocus && this.idleCount > FOCUSED_TICK_LIMIT) ||
								( ! this.hasFocus && this.idleCount > BLURRED_TICK_LIMIT);
			return (this.modalActive === false && mustShowModal === true);
		},

		logoutThresholdReached: function() {
			return (this.modalActive && this.idleCount > LOGOUT_TICK_LIMIT);
		},

		resolve: function() {
			if (this.logoutThresholdReached()) {
				Events.logout();
				$(window).trigger('broadcast.idleState', 'logout');
			}

			if (State.modalThresholdReached()) {
				Events.modal();
				$(window).trigger('broadcast.idleState', 'modal');
				$.get(EE.BASE + '&C=login&M=logout'); // log them out in the background to prevent tampering
			}

			// ping other windows if we've been reset
			if (this.idleCount == 0 && this.pingReceived === false) {
				$(window).trigger('broadcast.idleState', 'active');
			}

			// Reset
			State.pingReceived = false;
			this.eventsRecorded = [];
		}
	};

	/**
	 * List of events that might happen during our 15 second interval
	 */
	var Events = {

		// nothing has happened - we were idle
		_default: function() {
			State.idleCount += 1;
		},

		// received another window's active event, user active
		active: function() {
			State.idleCount = 0;
			State.hasFocus = false;
		},

		// user focused, they are active
		focus: function() {
			State.idleCount = 0;
			State.hasFocus = true;
		},

		// user left, they are idle
		blur: function() {
			State.idleCount = 1;
			State.hasFocus = false;
		},

		// user typing / mousing, possibly active
		interact: function() {
			if (State.hasFocus) {
				State.idleCount = 0;
			}
		},

		// received another window's modal event, open it
		modal: function() {
			if ( ! State.modalActive) {
				logoutModal.dialog('open');
				logoutModal.on('dialogbeforeclose', $.proxy(this, 'logout')); // prevent tampering

				State.modalActive = true;
			}

			State.idleCount += 1;
		},

		// received another window's login event, check and hide modal
		login: function() {
			$.get(EE.BASE + '&C=login&M=refresh_xid', function(result) {
				if (result.message != 'refresh') {
					return;
				}

				// refresh xid
				$('input[name="XID"]').filter('[value="'+EE.XID+'"]').val(result.xid);
				EE.XID = result.xid;

				// lose the modal
				logoutModal.off('dialogbeforeclose');
				logoutModal.dialog('close');

				State.modalActive = false;
				State.idleCount = 0;
			});
		},

		// received another window's logout event, leave page
		logout: function() {
			document.location = EE.BASE + '&C=login&M=logout';
		}
	};

	/**
	 * The event tracker spools up all events that happened during this tick
	 * and replays them when the timer fires.
	 */
	var EventTracker = {

		eventsRecorded: [],

		init: function() {
			this._bindEvents();
			this.tick();
		},

		tick: function() {
			// no events? run default
			if ( ! this.eventsRecorded.length) {
				this.eventsRecorded = ['_default'];
			}

			// replay events that happened during this tick
			_.each(this.eventsRecorded, function(evt) {
				Events[evt]();
			});

			State.resolve();

			setTimeout($.proxy(this, 'tick'), TICK_TIME);
		},

		/**
		 * Bind our events
		 *
		 * We keep track of focus, blur, scrolling, clicking, etc.
		 * Some broadcast events can be fired immediately as nothing will stop
		 * them once the tick fires anyways.
		 * We have an extra throttle on interactions to keep the browser happy
		 * and not fill up the queue uselessly.
		 */
		_bindEvents: function() {
			var track = $.proxy(this, '_track'),
				that = this;

			// Bind on the broadcast event
			$(window).on('broadcast.idleState', function(event, idleState) {

				switch (idleState) {
					case 'active':
						track(idleState);
						State.pingReceived = true;
						break;
					case 'modal':
					case 'login':
					case 'logout':
						Events[idleState]();
						break;
				}
			});

			// Bind on window focus and blur
			$(window).bind('blur', _.partial(track, 'blur'));
			$(window).bind('focus', _.partial(track, 'focus'));

			// Bind on interactions
			var interaction = 'DOMMouseScroll keydown mousedown mousemove mousewheel touchmove touchstart';
			$(document).on(
				interaction.split(' ').join('.idleState '),     // namespace the events
				_.throttle(_.partial(track, 'interact'), 2000)  // throttle event firing
			);

			// Clicking the logout button fires "modal"
			$('.logOutButton').click(function() {
				$(window).trigger('broadcast.idleState', 'modal');
			});
		},

		/**
		 * Helper method to record an event
		 */
		_track: function(name) {
			this.eventsRecorded.push(name);
		}
	};

	// Go go go!
	EventTracker.init();

	return Events;

})();


// Modal for "What does this mean?" link on deprecation notices
EE.cp.deprecation_meaning = function() {
	$('.deprecation_meaning').click(function(event) {
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

// Grid has become a dependency for a few fieldtypes. However, sometimes it's not
// on the page or loaded after the fieldtype. So instead of tryin to always load
// grid or doing weird dependency juggling, we're just going to cache any calls
// to grid.bind for now. Grid will override this definition and replay them if/when
// it becomes available on the page. Long term we need a better solution for js
// dependencies.
EE.grid_cache = [];

var Grid = {
	bind: function() {
		EE.grid_cache.push(arguments);
	}
};

// First step in deprecating scripts in add_to_head().
// Next release the message will be more visible/annoying.

(function() {
	var SCRIPT_COUNT = 2, // global_js, jquery
		scripts = $('head script');

	// anything but jquery and global_js shouldn't be there.
	if (scripts.length > SCRIPT_COUNT) {

		var fn = console.groupCollapsed ? 'groupCollapsed': 'log';
		console[fn]('Found third party scripts in <head> tag.');
		console.log('Please use cp->add_to_foot() to add scripts. jQuery and the EE global will be moved down in a future release.');

		scripts.slice(SCRIPT_COUNT).each(function() {
			console.log(this.src && this.src || '[Inline Script]');
		});

		if (fn == 'groupCollapsed') {
			console.groupEnd();
		}
	}
})();