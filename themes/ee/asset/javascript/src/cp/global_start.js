/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
		jqXHR.fail(function(xhr, status, error) {
			_.defer(function() {
				throw error;
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

// Handles events for the field manager channel create/edit form
var FluidField = window.FluidField = {

	_eventHandlers: [],

	/**
	 * Binds an event
	 *
	 * Available events are:
	 * 'add' - When a field is added
	 * 'remove' - When a field is removed
	 * 'beforeSort' - Before sort starts
	 * 'afterSort' - After sort ends
	 *
	 * @param	{string}	fieldtypeName	Class name of fieldtype
	 * @param	{string}	action	Name of action
	 * @param	{func}		func	Callback function for event
	 */
	on: function(fieldtypeName, action, func) {
		if (this._eventHandlers[action] == undefined) {
			this._eventHandlers[action] = []
		}

		// Each fieldtype gets one method per handler
		this._eventHandlers[action][fieldtypeName] = func;
	},

	/**
	 * Fires an event
	 *
	 * @param	{string}	action	Name of action
	 * @param	{object}	element	Element object to pass along to the callback
	 */
	fireEvent: function(fieldtypeName, action, args) {
		// If no events regsitered, don't bother
		if (this._eventHandlers[action] === undefined) {
			return;
		}

		// If no events regsitered, don't bother
		if (this._eventHandlers[action][fieldtypeName] === undefined) {
			return;
		}

		this._eventHandlers[action][fieldtypeName].apply(this, args);
	}
};

// Handles events for the field manager channel create/edit form
var FieldManager = window.FieldManager = {

	_eventHandlers: [],

	/**
	 * Binds an event
	 *
	 * Available events are:
	 * 'fieldModalDisplay' - When the new field modal form is displayed
	 *
	 * @param	{string}	action	Name of action
	 * @param	{func}		func	Callback function for event
	 */
	on: function(action, func) {
		if (this._eventHandlers[action] == undefined) {
			this._eventHandlers[action] = []
		}

		this._eventHandlers[action].push(func)
	},

	/**
	 * Fires an event
	 *
	 * @param	{string}	action	Name of action
	 * @param	{object}	element	Element object to pass along to the callback
	 */
	fireEvent: function(action, element) {
		var handlers = this._eventHandlers[action] || []

		for (var i = 0; i < handlers.length; i++) {
			handlers[i](element)
		}
	}
};

// Setup Base EE Control Panel
$(document).ready(function () {

	// call the input placeholder polyfill early so that we don't get
	// weird flashes of content
	if ( ! 'placeholder' in document.createElement('input')) {
		EE.insert_placeholders();
	}

	EE.cp.cleanUrls();
	EE.cp.bindCpMessageClose();
	EE.cp.bindSortableFolderLists();
	EE.cp.addLastToChecklists();
	EE.cp.bindPostLinks();
});

// Scroll to version popover on successful update
if (EE.cp.updateCompleted) {
	$('.app-about-info').show()
	$('.app-about-info__update').hide()
	$('html, body').animate({
		scrollTop: $('.app-about-info').offset().top
	}, 500)
}

/**
 * Finds links with a data-post-url attribute and on click, fires off a POST
 * request to that URL via a form submission. This is so that certain actions
 * can have the protection from CSRF but still be regular links in the UI.
 */
EE.cp.bindPostLinks = function() {
	$('body').on('click', 'a[data-post-url]', function(event) {
		event.preventDefault();

		var form = $('<form/>', {
			action: $(this).data('postUrl'),
			method: 'post'
		});
		form.append($('<input/>', {
			name: 'csrf_token',
			value: EE.CSRF_TOKEN
		}));
		form.appendTo('body').submit();
	});
}

/**
 * For nested checklists, the very last item that APPEARS in the list
 * needs a class of "last".
 */
EE.cp.addLastToChecklists = function() {
	$('ul.nested-list li').removeClass('last');

	$('ul.nested-list').each(function(){
		$('li:last-child', this).not('ul.toolbar li').last().addClass('last');
	});
}

// Close alert modal when close button is clicked
EE.cp.bindCpMessageClose = function() {
	$('body').on('click', '.js-notice-dismiss', function(event) {
		event.preventDefault();
		$(this).closest('.app-notice').remove();
	});

	// Clear floating alerts after some time
	var floatingAlerts = $('.app-notice--alert')
	if (floatingAlerts.size()) {
		setTimeout(function() {
			floatingAlerts.fadeOut(function() {
				floatingAlerts.remove()
			})
		}, 20000)
	}
}

// Binds jQuery UI sortable to reorderable folder lists
EE.cp.bindSortableFolderLists = function() {
	$('.sidebar .folder-list.reorderable').sortable({
		axis: 'y',						// Only allow vertical dragging
		containment: 'parent',			// Contain to parent
		handle: 'a',					// Set drag handle
		cancel: 'ul.toolbar',			// Do not allow sort on this handle
		items: '> li',					// Only allow these to be sortable
		sort: EE.sortable_sort_helper,	// Custom sort handler
		// After sort finishes
		stop: function(event, ui)
		{
			var name = $(this).data('name'), i;

			if (EE.cp.folderList.eventHandlers[name] === undefined) {
				return;
			}

			// A list may have multiple callbacks, call them all
			for (i = 0; i < EE.cp.folderList.eventHandlers[name].length; i++) {
				EE.cp.folderList.eventHandlers[name][i]($(this));
			}
		}
	});
}

EE.cp.folderList = {

	eventHandlers: [],

	/**
	 * Binds a callback to the sort event of a folder list
	 *
	 * @param	{string}	listName	Unique name of folder list
	 * @param	{func}		func		Callback function for event
	 */
	onSort: function(listName, func) {
		if (this.eventHandlers[listName] == undefined) {
			this.eventHandlers[listName] = [];
		}

		this.eventHandlers[listName].push(func);
	}
};

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

$(window).bind('broadcast.setRememberMe', function(event, remember) {
	EE.hasRememberMe = remember;
})

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

/**
 * Handle idle / inaction between windows
 *
 * This code relies heavily on timing. In order to reduce complexity everything is
 * handled in steps (ticks) of 1 second. We count for how many ticks we have been
 * in a given state and act accordingly. This gives us reasonable timing information
 * without having to set, cancel, and track multiple timeouts.
 *
 * The conditions currently are as follows:
 *
 * - If an ee tab has focus we call it idle after 30 minutes of no interaction
 * - If no ee tab has focus, we call it idle after 45 minutes of no activity
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

		// May be inside a modal form or elsewhere
		if ( ! logoutModal) {
			return;
		}

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
			if ( ! State.modalActive && logoutModal) {

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
			$(window).trigger('broadcast.setRememberMe', EE.hasRememberMe);
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
