/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*!
 * ExpressionEngine Custom Interact jQuery Event
 */

/* Usage Notes:
 *
 * This file adds a custom event to jquery. The broadcast event
 * handles interactions between windows and tabs of the current
 * browser. It is useful for communicating global events, such
 * as showing the login modal when a tab becomes idle, or hiding
 * the sidebar across all windows.
 *
 * The postmessage api requires that you already have a reference
 * to the window object you're sending to. Since these cannot be
 * grabbed, we instead use Local Storage as a proxy. When a store
 * is changed, the "storage" event is fired on all tabs/windows
 * using the same item.
 *
 * We send along a unique ID for this window as well as the event
 * namespace if it exists. The message is sent through storage as
 * a json encoded string. This gives us a 44 byte overhead that
 * will be taken from the 4kb cookie limit. It is generally not
 * wise to send large messages.
 *
 * Usage:
 *
 * $(window).bind('broadcast', function(evt, message) {...});
 * $(window).trigger('broadcast', "My Message");
 *
 */
(function($) {

/**
 * Helper function to insure we have local storage support.
 *
 * Some browsers will throw a quota exceeded exception if you
 * try to write to local storage while in "private browsing" mode,
 * so we attempt to set a tiny dummy item to detect this case.
 *
 * @return bool LocalStorage available?
 */
function localStorageSupported() {
	try {
		if ('localStorage' in window && window['localStorage'] !== null)
		{
			localStorage.setItem('ee_ping', 1);
			localStorage.removeItem('ee_ping');
			return true;
		}
	} catch (e) {
		return false;
	}
}

/**
 * Grab our data store
 *
 * If local storage is full or not supported we will fall back to a cookie
 * store with the same api as local storage. This means that our message
 * size limit is much lower than just using local storage would be, but it
 * is also much more robust.
 */
var store = localStorageSupported() ? localStorage : {

	setItem: function(k, v) {
		var time = new Date();
		time.setTime(time.getTime() + 5 * 1000); // expire in 5 seconds
		document.cookie = k + '=' + escape(v) + '; expires='+ time.toGMTString() +'; path=/';
	},

	removeItem: function(k) {
		document.cookie = k + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT';
	},

	getItem: function(k) {
		var regex = new RegExp('[,; ]' + k + '=([^\\s,;]*)'),
			cookies = ' ' + document.cookie,
			match = cookies.match(regex);

		return match ? unescape(match[1]) : undefined;
	}
};

/**
 * Generate a unique-ish id by combining the current microtime with
 * an rfc4122 version 4 random field.
 *
 * @return String 17 character unique id
 */
function uniqueId() {
	return $.now() + (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
}


/**
 * Setup a few constants
 *
 * All of our broadcasts are done sequentially through a single key.
 * After a broadcast is sent, the sender will wait for a moment while
 * other windows read the message, and then it will delete it.
 * This way we can unambiguously send the same message repeatedly. The
 * cookie store must poll the cookie (1000ms) rather than using an event,
 * so the delay waiting for others to read is significantly larger.
 */
var WINDOW_UID = uniqueId(),
	BROADCAST_KEY = 'ee_broadcast',
	READ_DELAY = localStorageSupported() ? 20 : 1500,
	$window = $(window);

EE.BROADCAST_UID = WINDOW_UID;

/**
 * Conflict resolution
 *
 * Messages sent in quick succession need to be queued and sent in order
 * Messages sent by this window should not be read by it (can happen with cookies)
 */
var Arbiter = {

	_queue: [],					// message queue
	_waiting: false,			// queue running
	_lastMessage: undefined,	// last sent

	/**
	 * Add the message to the queue and dequeue if no
	 * message is currently posted.
	 */
	sendMessage: function(message) {
		this._queue.push(function(next) {
			store.setItem(BROADCAST_KEY, message);
			Arbiter._lastMessage = message;

			setTimeout(function() {
				store.removeItem(BROADCAST_KEY);
				Arbiter._lastMessage = undefined;
				next()
			}, READ_DELAY);
		});

		this.dequeue();
	},

	/**
	 * Receive the messages. If the message was posted by this window
	 * we will ignore it. Otherwise pass it on to the correct event handlers.
	 */
	receiveMessage: function(message) {

		var value = JSON.parse(message),
			ns = value.ns ? '.' + value.ns : '',
			uid = value.uid;

		if (uid == WINDOW_UID) {
			return;
		}

		$window.trigger({
			type: '_broadcastMessage'+ns,
			sender: uid,
			receiver: WINDOW_UID
		}, value.data);
	},

	/**
	 * Queue helper. Passes a "next" function to the
	 * callback that will continue the dequeuing process.
	 */
	dequeue: function() {
		if (this._waiting) {
			return;
		}

		this._waiting = true;
		var fn = this._queue.shift();
		fn(function() {
			Arbiter._waiting = false;

			if (Arbiter._queue.length) {
				Arbiter.dequeue();
			}
		});
	}

};

/**
 * While sending messages is easy to hide away in the store, the two
 * systems need a slightly differing receiver setup.
 */
var Receivers = {

	/**
	 * LocalStorage can use the storage event. This is pretty simple, but we
	 * do need to make sure to ignore non-broadcast data and we should not
	 * fire after blanking out the message.
	 */
	local: {
		setup: function() {
			$window.on('storage', function(event) {
				var orig = event.originalEvent;

				if (orig.key != BROADCAST_KEY || ! orig.newValue) {
					return;
				}

				Arbiter.receiveMessage(orig.newValue);
			});
		},

		teardown: function() {
			$window.off('storage');
		}
	},

	/**
	 * For cookies we must poll for changes. We should not read the same
	 * message twice, so we keep track of the last received message.
	 */
	cookie: {

		_timer: null,

		setup: function() {
			var oldValue = undefined;

			function read() {
				var value = store.getItem(BROADCAST_KEY);

				if (value != oldValue) {
					Arbiter.receiveMessage(value);
					oldValue = value;
				}

				// check the cookie for changes every second
				Receivers.cookie._timer = setTimeout(read, 1000);
			}

			read();
		},

		teardown: function() {
			clearTimeout(Receivers.cookie._timer);
		}
	}
};



/**
 * Setup the jquery event interactions.
 *
 * Since our broadcast name is overloaded to trigger on the other windows, we
 * cannot use that same name to trigger our bound event handlers. To work around
 * this we bind a separate local message event.
 */
$.event.special.broadcast = {

	/**
	 * On first bind, setup the message system on the window element
	 */
	setup: function() {
		Receivers[ localStorageSupported() ? 'local' : 'cookie' ].setup();
	},

	/**
	 * Bind the local messaging event for the given handler
	 */
	add: function(event) {
		var ns = (event.namespace) ? '.' + event.namespace : '';
		$.event.add(this, '_broadcastMessage' + ns, event.handler);
	},

	/**
	 * Trigger events of this name on the other tabs
	 */
	trigger: function(event, data) {
		if (event.target != window) {
			return;
		}

		// This may be stored in a cookie, so we keep the keys small
		var message = JSON.stringify({
			ns: event.namespace,
			uid: WINDOW_UID,
			data: $.makeArray(arguments).slice(1)
		});

		Arbiter.sendMessage(message);

		return false;
	},

	/**
	 * Cleanup and unbind events
	 */
	teardown: function(namespaces) {
		$(this).unbind('_broadcastMessage');
		Receivers[ localStorageSupported() ? 'local' : 'cookie' ].teardown();
	}
};

})(jQuery);
