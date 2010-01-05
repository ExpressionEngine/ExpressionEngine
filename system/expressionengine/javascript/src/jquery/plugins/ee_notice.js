/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/*!
 * ExpressionEngine JS Notification Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

/* Usage Notes:
 *
 * $.ee_notice(message, options);
 *
 * message			- the text/html to show (required)
 *					- can also be an array of objects: [{message:"text"}, {message:"another", type:"error"}]
 *
 * Options:
 * type				- message severity, options are: notice | success | error (default = notice)
 * delay			- delay before notification is shown (default = 0)
 * duration			- time it remains visible (0 = until closed | default = 5 sec)
 * animation_speed	- duration of the sliding animation
 *
 * Force Hide:
 * $.ee_notice.destroy()
 */

/* Example:
 *
 * $.ee_notice("Entry saved. <a href='#'>Look here!</a>", {
 *		'duration' : 0
 * });
 *
 */
(function($) {

	var message_queue = [],
		rm_class = /\bjs_.*?\b/g,
		delay_active = false,
		options, close_timeout, nc_height,
		notice_container, notice_text, notice_inner;

	/**
	 * Create a notification
	 */
	$.ee_notice = function(messages, params) {
		options = $.extend({
			type: 'notice',
			delay: 0,
			duration: 3000,
			animation_speed: 400
		}, params);
		
		if (message_queue.length) {
			delay_active = true;
		}
		
		if (typeof messages == "string") {
			messages = [{message: messages}];
		}
		
		messages = $.map(messages, function (m) {
			m.type = m.type || options.type;
			m.duration = m.duration || (m.type == 'error') ? 0 : options.duration;
			return m;
		});
		message_queue.push.apply(message_queue, messages);
		
		// Patience!
		if (delay_active === false) {
			delay_active = true;
			
			setTimeout(function() {
				create_container();
				dequeue_message();
			}, options.delay);
		}
		
		return $.ee_notice;
	}
	
	
	/**
	 * Remove notification in one fell swoop (kills queue)
	 */
	$.ee_notice.destroy = function() {
		message_queue = [];
		if (notice_container) {
			hide_notice();
		}
	}
	
	/**
	 * Creates the container and binds events
	 */
	function create_container() {
		if ( ! notice_container) {
			var close_handle = $('<div class="close_handle"><a href="#">&times;</a></div>');
			notice_text = $('<span/>');

			notice_inner = $('<div class="notice_inner"/>').append(notice_text, close_handle);
			notice_container = $('<div class="js_notification"/>').append(notice_inner).appendTo(document.body);

			close_handle.click(hide_notice);
			
			// Pause timeout on hover and restart with very small delay on mouseout
			notice_container.hover(function() {
				clear_timeout(500);
			}, start_timeout);
			
			// Clicking dismisses on mouseout
			notice_container.click(function() {
				clear_timeout(1);
			});
		}
	}
	
	/**
	 * Clear the timeout
	 */
	function clear_timeout(new_d) {
		if (typeof close_timeout == "number") {
			window.clearTimeout(close_timeout);
		}
		
		if (new_d && message_queue[0].duration) {
			message_queue[0].duration = new_d;
		}
	}
	
	/**
	 * Starts the hiding timeout
	 */
	function start_timeout() {
		clear_timeout();
		
		// No duration? no hiding!
		if (message_queue.length && message_queue[0].duration) {
			close_timeout = window.setTimeout(hide_notice, message_queue[0].duration);
		}
	}
	
	/**
	 * Dequeues the next message
	 */
	function dequeue_message() {
		delay_active = false;
		
		if ( ! message_queue.length) {
			return clear_timeout();
		}
				
		// Add proper type class
		notice_inner[0].className = notice_inner[0].className.replace(rm_class, '');
		notice_inner.addClass('js_'+message_queue[0].type);
		
		notice_text.html(message_queue[0].message);
		
		// Go into hiding
		nc_height = notice_container.outerHeight();
		notice_container.css('top', -nc_height);

		// Show, slide down, and start hiding timeout
		notice_container.show().animate({'top': 0}, options.animation_speed, start_timeout);
	}


	/**
	 * Hides the notification container
	 */
	function hide_notice() {
		message_queue = message_queue.slice(1);
		notice_container.animate({'top': -nc_height}, options.animation_speed, dequeue_message);
		return false; // kill click event
	}
	
})(jQuery);

/* End of file ee_notice.js */
/* Location: ./system/expressionengine/javascript/jquery/plugins/ee_notice.js */