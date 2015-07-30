/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function($) {

"use strict";

 /**
  * Namespace function that non-destructively creates "namespace" objects (e.g. EE.publish.example)
  *
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
	// create a property if it doesn't exist
	for (var i = 0, max = parts.length; i < max; i += 1) {
		if (typeof parent[parts[i]] === "undefined") {
			parent[parts[i]] = {};
		};

		parent = parent[parts[i]];
	}

	return parent;
};

// Create the base cp namespace
EE.namespace('EE.cp');

/**
 * Hook into jQuery's ajax functionality to build in handling of our
 * csrf tokens and custom response headers.
 *
 * We also add a custom error handler in case the developer does not specify
 * one. This prevents silent failure.
 */
$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
	var old_token = EE.CSRF_TOKEN,
		type = options.type.toUpperCase();

	// Throw all errors
	if ( ! _.has(options, 'error')) {
		jqXHR.error(function(data) {
			_.defer(function() {
				throw [data.statusText, data.responseText];
			});
		});
	}

	// Add CSRF TOKEN to EE POST requests
	if (type == 'POST' && options.crossDomain === false) {
		jqXHR.setRequestHeader('X-CSRF-TOKEN', old_token);
	}

	var defaultHeaderResponses = {
		// Refresh xids (deprecated)
		'eexid': function(new_xid) {
			if (new_xid) {
				EE.cp.setCsrfToken(new_xid);
			}
		},

		// Refresh csrf tokens
		'csrf-token': function(new_token) {
			if (new_token) {
				EE.cp.setCsrfToken(new_token);
			}
		},

		// Force redirects (e.g. logout)
		'ee-redirect': function(url) {
			window.location = EE.BASE + '&' + url.replace('//', '/'); // replace to prevent //example.com
		},

		// Trigger broadcast events
		'ee-broadcast': function(event) {
			EE.cp.broadcastEvents[event]();
			$(window).trigger('broadcast', event);
		}
	};

	// Set EE response header defaults
	var eeResponseHeaders = $.merge(
		defaultHeaderResponses,
		originalOptions.eeResponseHeaders || {}
	);

	jqXHR.complete(function(xhr) {

		if (options.crossDomain === false) {
			_.each(eeResponseHeaders, function(callback, name) {
				var headerValue = xhr.getResponseHeader('X-'+name);

				if (headerValue) {
					callback(headerValue);
				}
			});
		}
	});
});


// Grid has become a dependency for a few fieldtypes. However, sometimes it's not
// on the page or loaded after the fieldtype. So instead of tryin to always load
// grid or doing weird dependency juggling, we're just going to cache any calls
// to grid.bind for now. Grid will override this definition and replay them if/when
// it becomes available on the page. Long term we need a better solution for js
// dependencies.
EE.grid_cache = [];

window.Grid = {
	bind: function() {
		EE.grid_cache.push(arguments);
	}
};


// Setup Base EE Control Panel
$(document).ready(function () {

	// call the input placeholder polyfill early so that we don't get
	// weird flashes of content
	if ( ! 'placeholder' in document.createElement('input')) {
		EE.insert_placeholders();
	}

	// Notice banners
	if (EE.importantMessage) {
		EE.cp.showNoticeBanner();
	}

	EE.cp.zebra_tables();
	EE.cp.show_hide_sidebar();
	EE.cp.display_notices();
	EE.cp.cleanUrls();
	EE.cp.deprecation_meaning();
	EE.cp.notepad.init();
	EE.cp.accessory_toggle();
	EE.cp.control_panel_search();
	EE.cp.bindCpMessageClose();
	EE.cp.channelMenuFilter();

	// Setup sidebar hover descriptions
	$('#quickLinks h4').click(function () {
		window.location.href = EE.BASE + '&C=myaccount&M=quicklinks';
	})
	.add('#notePad').hover(function () {
		$('.sidebar_hover_desc', this).show();
	}, function () {
		$('.sidebar_hover_desc', this).hide();
	})
	.css('cursor', 'pointer');

});

// Binds the channel filter text boxes in Create and Edit menus
EE.cp.channelMenuFilter = function() {

	var filters = $('.menu-wrap form.filter input, .filter-search input');

	// Bail if no filters
	if (filters.size() == 0) {
		return;
	}

	// Create a style element where we'll input the CSS needed
	// to filter the table
	var searchStyle = $('<style/>');
	$('body').append(searchStyle);

	// Watch the filter input on keyup and then filter the results
	filters.bind('keyup', function()
	{
		// Text box blank? Reset table to show all results
		if ( ! this.value)
		{
			searchStyle.html('');
			return;
		}

		// Grab the class of the list to make sure we filter the right one
		var listClass = $(this).parent().siblings('ul').attr('class');

		// Data is indexed via a data attribute, create a CSS
		// selector to filter the table
		searchStyle.html('ul.'+listClass+' li.search-channel:not([data-search*="' + this.value.toLowerCase() + '"]) { display: none; }');
	});
}

// Close alert modal when close button is clicked
EE.cp.bindCpMessageClose = function() {
	$('div.alert a.close').click(function(event)
	{
		event.preventDefault();
		$(this).parent().hide();
	});
}


// Simple function to deal with csrf tokens
EE.cp.setCsrfToken = function(newToken, skipBroadcast /* internal */) {
	$('input[name="XID"]').val(newToken);
	$('input[name="csrf_token"]').val(newToken);

	EE.XID = newToken;
	EE.CSRF_TOKEN = newToken;

	if ( ! skipBroadcast) {
		$(window).trigger('broadcast.setCsrfToken', newToken);
	}
};

$(window).bind('broadcast.setCsrfToken', function(event, data) {
	EE.cp.setCsrfToken(data, true);
});


// Simple function to deal with base paths tokens
var sessionIdRegex = /[&?](S=[A-Za-z0-9]+)/;

EE.cp.setBasePath = function(newBase, skipBroadcast /* internal */) {

	var newBase = newBase.replace(/&amp;/g, '&'),
		newBaseS = newBase.match(sessionIdRegex) || ['', ''],
		oldBaseS = EE.BASE.match(sessionIdRegex) || ['', ''];

	var replaceBase = function(i, value) {
		if (value) {
			return value.replace(oldBaseS[1], newBaseS[1]);
		}
	};

	$('a').attr('href', replaceBase);
	$('form').attr('action', replaceBase);

	// Since the session id in the current url is no longer correct, a
	// refresh will end up on the login page. We will replace the current
	// url to avoid that issue. You still cannot use the back button after
	// logging back in, but how likely are you to remember what page you
	// were on before leaving this one open for 20 minutes anyways?
	if (typeof window.history.pushState == 'function') {
		window.history.replaceState(
			null,
			document.title,
			window.location.href.replace(oldBaseS[1], newBaseS[1])
		);
	}

	// Set it as the new base
	EE.BASE = newBase;

	if ( ! skipBroadcast) {
		$(window).trigger('broadcast.setBasePath', newBase);
	}
};

$(window).bind('broadcast.setBasePath', function(event, data) {
	EE.cp.setBasePath(data, true);
});


EE.cp.refreshSessionData = function(event, base) {
	if (base) {
		EE.cp.setBasePath(base);
	}

	// running the request will return the x-csrf-header, which will trigger
	// our prefilter. We still need to replace the base though.
	$.getJSON(EE.BASE + '&C=login&M=refresh_csrf_token', function(result) {
		EE.cp.setBasePath(result.base);
	});

};


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

var urlRegex = /(.*?)[?](.*?&)?(D=cp(?:&C=[^&]+(?:&M=[^&]+)?)?)(?:&(.+))?$/,
	slashify = /&?[DCM]=/g,
	lTrimAmp = /^&+/,
	rTrimAmp = /&+$/,
	removeEmptySession = /(^|&)S=0(&|$)/;

EE.cp.cleanUrl = function(i, url) {
	url = url || i; // i exists if coming from jQuery attr callback
	url = url || '';

	// Move session to the end
	url = url.toString().replace(/^(\S*?)S=(\S+?)&(\S*?)$/g, "$1$3&S=$2");

	var result = urlRegex.exec(url);

	if ( ! result) {
		return;
	}

	// result[1] // index.php
	// result[2] // S=49204&
	// result[3] // D=cp&C=foo&M=bar
	// result[4] // &foobarbaz

	var path   = result[3].replace(slashify, '/'),
		preQs  = result[2] || '',
		postQs = result[4] || '',
		newUrl = result[1] + '?' + path;

	var QS = postQs.replace(removeEmptySession, '') + '&' + preQs.replace(removeEmptySession, '');

	QS = QS.replace(lTrimAmp, '').replace(rTrimAmp, '');

	if (QS) {
		newUrl += '&' + QS;
	}

	return newUrl.replace(rTrimAmp, '');
};

EE.cp.cleanUrls = function() {
	$('a:not([href^=javascript])').attr('href', EE.cp.cleanUrl);
	$('form').attr('action', EE.cp.cleanUrl);
};


// Upgrade and developer log notices
EE.cp.showNoticeBanner = function() {
	var msgBoxOpen, msgContainer, save_state, setup_hidden;
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

	if ( ! msgBoxOpen) {
		setup_hidden();
	}
};

// Setup Notepad
EE.cp.notepad = (function () {

	var notepad_form,
		notepad_txtarea,
		notepad_controls,
		notepad_text,
		notepad_empty,
		current_content;

	return {
		init: function () {

			var notepad = $('#notePad');

			notepad_form = $("#notepad_form");
			notepad_txtarea = $('#notePadTextEdit');
			notepad_controls = $('#notePadControls');
			notepad_text = $('#notePadText');
			notepad_empty = notepad_text.text();
			current_content = notepad_txtarea.val();

			if (current_content) {
				notepad_text.html(current_content.replace(/</ig, '&lt;').replace(/>/ig, '&gt;').replace(/\n/ig, '<br />'));
			}

			notepad.click(EE.cp.notepad.show);
			notepad_controls.find('a.cancel').click(EE.cp.notepad.hide);

			notepad_form.submit(EE.cp.notepad.submit);
			notepad_controls.find('input.submit').click(EE.cp.notepad.submit);

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
})();

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

		var selector = (sidebarIsOpen) ? "#revealSidebarLink" : "#hideSidebarLink";

		$(selector).filter(':visible').trigger('click');
	});
};

// Move notices to notification bar for consistency
EE.cp.display_notices = function() {

	var types = ["success", "notice", "error"];

	$(".message.js_hide").each(function() {
		for (var i in types) {
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

		var input = $(this),
			placeholder = this.placeholder,
			orig_color = input.css('color');

		if (input.val() == '') {
			input.data('user_data', 'n');
		}

		input.focus(function () {
			// Reset color & remove placeholder text
			input.css('color', orig_color);
			if (input.val() === placeholder) {
				input.val('');
				input.data('user_data', 'y');
			}
		})
		.blur(function () {
			// If no user content -> add placeholder text and dim
			if (input.val() === '' || input.val === placeholder) {
				input.val(placeholder).css('color', '#888');
				input.data('user_data', 'n');
			}
		})
		.trigger('blur');
	});
};

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
		.removeClass('alt')
		.filter(':even').addClass('alt')
		.end()
		.filter(':first').removeClass('alt');
};

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
 * - If they work around the modal (inspector), all request will land on the login page.
 * - Logging out of one tab will show the modal on all other tabs.
 * - Logging into the modal on one tab, will show it on all other tabs.
 *
 * The object returned is one that allows manual triggering of an event. For
 * example, to force the modal to show you could call:
 *
 *     EE.cp.broadcastEvents['modal']();
 *
 * This is used by our ajax filter to allow triggering an event with the
 * X-EE-BROADCAST header
 *
 */
EE.cp.broadcastEvents = (function() {

	// Define our time limits:
	var TICK_TIME          = 1 * 1000,			// Check state every second
		FOCUSED_IDLE_LIMIT = 30 * 60 * 1000,	// 30 minutes: time before modal if window focused
		BLURRED_IDLE_LIMIT = 45 * 60 * 1000,    // 45 minutes: time before modal if no focus
		REFRESH_TIME_LIMIT = 50 * 60 * 1000,	// 50 minutes: refresh if active or remember me
		logoutModal,
		overlay;

	// Setup Base EE Control Panel
	$(document).ready(function () {

		// Make sure we have our modal available when we need it
		logoutModal = $('#idle-modal'),
		overlay		= $('.overlay');

		// If the modal hasn't been interacted with in over 10 minutes we'll send a request for
		// the current csrf token. It can flip on us during long waits due to the session timeout.
		// If the session times out this will get us a cookie based csrf token, which is what you
		// would normally log in with, so it's fine.
		logoutModal.find('form').on('interact', _.throttle(EE.cp.refreshSessionData, 10 * 60 * 1000));

		// Bind on the modal submission
		logoutModal.find('form').on('submit', function() {

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
					Events.login();

					// Grab the new token
					EE.cp.refreshSessionData(null, result.base);

					$(window).trigger('broadcast.idleState', 'login');
				},

				error: function(data) {
					alert(data.message);
				}
			});

			return false;
		});

	});

	/**
	 * This object tracks the current state of the page.
	 *
	 * The resolve function is called once per tick. The individual events will
	 * set hasFocus and lastActive time.
	 */
	var State = {

		hasFocus: true,
		modalActive: false,
		pingReceived: false,
		lastActive: $.now(),
		lastRefresh: $.now(),

		setActiveTime: function() {
			// Before we set someone as not idle we need to check if they've
			// sneakily been idle for a long time. When you close your laptop
			// the timer stops. Reopening it hours later creates a race between
			// the tick timer and the non-idle events. When that happens, you're
			// way past the threshold and therefore too late.
			if (this.modalActive || ! this.modalThresholdReached()) {

				// If they're active on the page for an extend period of time
				// without hitting the backend, we can sometimes run past the
				// session timeout. To prevent that from happening we'll refresh
				// their session last activity in the background.
				if (this.refreshThresholdReached()) {
					this.doRefresh();
				}

				this.lastActive = $.now();
			}
		},

		modalThresholdReached: function() {
			var idleTimeDelta = $.now() - this.lastActive,
				mustShowModal = (this.hasFocus && idleTimeDelta > FOCUSED_IDLE_LIMIT) ||
								( ! this.hasFocus && idleTimeDelta > BLURRED_IDLE_LIMIT);
			return (this.modalActive === false && mustShowModal === true);
		},

		refreshThresholdReached: function() {
			var refreshTimeDelta = $.now() - this.lastRefresh;
			return refreshTimeDelta > REFRESH_TIME_LIMIT;
		},

		doRefresh: function() {
			this.lastRefresh = $.now();
			EE.cp.refreshSessionData();
		},

		resolve: function() {

			if (EE.hasRememberMe) {
				if (this.refreshThresholdReached()) {
					this.doRefresh();
				}

				return;
			}

			if (this.modalThresholdReached()) {
				Events.modal();
				$(window).trigger('broadcast.idleState', 'modal');
				$.get(EE.BASE + '&C=login&M=lock_cp'); // lock them out of the cp in the background to prevent tampering
			}
			else if (this.hasFocus && this.pingReceived === false) {
				$(window).trigger('broadcast.idleState', 'active');
			}

			// Reset
			this.pingReceived = false;
		}
	};

	/**
	 * List of events that might happen during our 15 second interval
	 */
	var Events = {

		// received another window's active event, user active
		active: function() {
			State.setActiveTime();
		},

		// user focused, they are active
		focus: function() {
			State.setActiveTime();
			State.hasFocus = true;
		},

		// user left, they are idle
		blur: function() {
			State.setActiveTime();
			State.hasFocus = false;
		},

		// user typing / mousing, possibly active
		interact: function() {
			if (State.hasFocus) {
				State.setActiveTime();
			}
		},

		// received another window's modal event, open it
		modal: function() {
			if ( ! State.modalActive) {

				logoutModal.trigger('modal:open');

				logoutModal.on('modal:close', function(e) {
					if (State.modalActive)
					{
						e.preventDefault();
						Events.logout(); // prevent tampering. If they close it, they go.
					}
				});

				State.modalActive = true;
			}

			State.setActiveTime();
		},

		// received another window's login event, check and hide modal
		login: function() {
			State.modalActive = false;

			logoutModal.trigger('modal:close');

			logoutModal.find(':password').val('');

			State.setActiveTime();
		},

		// received another window's logout event, leave page
		logout: function() {
			window.location = EE.BASE + '&C=login&M=logout';
		}
	};

	/**
	 * The event tracker spools up all events that happened during this tick
	 * and replays them when the timer fires.
	 */
	var EventTracker = {

		_t: null,

		init: function() {
			$(window).trigger('broadcast.setBasePath', EE.BASE);
			$(window).trigger('broadcast.setCsrfToken', EE.CSRF_TOKEN);
			$(window).trigger('broadcast.idleState', 'login');

			this._bindEvents();
			this.track();
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
			var track = $.proxy(this, 'track'),
				that = this;

			// Bind on the broadcast event
			$(window).on('broadcast.idleState', function(event, idleState) {

				switch (idleState) {
					case 'active':
						State.pingReceived = true;
						track(idleState);
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
				_.throttle(_.partial(track, 'interact'), 500)  // throttle event firing
			);

			// Clicking the logout button fires "modal" on all the others
			$('.logOutButton').click(function() {
				$(window).trigger('broadcast.idleState', 'modal');
			});
		},

		/**
		 * Helper method to record an event
		 */
		track: function(name) {
			clearTimeout(this._t);
			this._t = setTimeout($.proxy(this, 'track'), TICK_TIME);

			if (name) {
				Events[name]();
			}

			State.resolve();
		}
	};

	// Go go go!
	EventTracker.init();

	return Events;

})();


})(jQuery);
