/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
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
 * open				- open drawer automatically | bool (default = false [except for errors])
 * close_on_click   - close the drawer on click. | bool (default = true)
 *
 * Force Hide:
 * $.ee_notice.destroy()
 */
(function($) {
	
	var type_store = {},
		info_callback = function() {},	// @todo move to $.noop in 1.4
		store_ref, drawer, options, active;

	function _init() {
		var type_counts = $("#notice_counts");
		
		// Set plugin globals
		type_store = {
			"error"	  : { count: 0,  last: "",  counter: type_counts.find(".notice_error").get(0) },
			"alert"	  : { count: 0,  last: "",  counter: type_counts.find(".notice_alert").get(0) },
			"success" : { count: 0,  last: "",  counter: type_counts.find(".notice_success").get(0) }
		};
		
		drawer = $("#notice_texts_container");
		
		// Set up events
		type_counts.find('span').click(count_click_handler);
	}
	
	$.ee_notice = function(message, params) {
		if ( ! drawer) {
			_init();
		}
		
		params = params || {};
				
		if ($.isArray(message)) {
			$.each(message ,function(k, v) {
				$.ee_notice(v.message, $.extend(params, v));
			});
			return;
		}
		
		options = $.extend({
			type: 'notice',
			open: false,
			close_on_click: true
		}, params);
		
		// Ha! Well done, Pascal
		if (options.type == 'notice') {
			options.type = 'alert';
		}
		
		store_ref = type_store[options.type];

		if (store_ref) {
			
			increment_type_count();
			
			var notice_list = $(".notice_texts.notice_" + options.type);
			
			if (store_ref.last == message) {
				var last_message = notice_list.children().slice(-1),
					subcount = last_message.find('.subcount');
				
				if ( ! subcount.length) {
					last_message.prepend('<span class="subcount">'+2+'</span>');
				}
				else {
					subcount.text(parseInt(subcount.text()) + 1);
				}
			}
			else {
				notice_list.append("<p>" + message + "</p>");
				store_ref.last = message;
			}
		}
		else if (options.type == 'custom') {
			$(".notice_texts.notice_custom").html(message);
		}
		else {
			throw "Invalid notification type.";
		}
		
		if (options.type == 'error' || options.open) {
			open_notice_drawer(options.type);
		}
		
		return $.ee_notice;
	}
	
	$.ee_notice.destroy = function() {
		if (drawer) {
			close_notice_drawer();
		}
	}
	
	$.ee_notice.show_info = function(click_callback) {
		if ( ! drawer) {
			_init();
		}
		
		$("#notice_flag").css("display", "inline");
		$('.notice_info').show();
		info_callback = click_callback;
		
		repaint_flag();
	}
	
	$.ee_notice.hide_info = function() {
		$('.notice_info').hide();
		var total = 0;
		
		$.each(type_store, function(k, v) {
			total += v.count;
		});
		
		if ( ! total) {
			$("#notice_flag").hide();
		}
	}
	
	function repaint_flag() {
		if ( ! $.browser == 'safari') {
			return;
		}
		setTimeout(function() {
			window.scrollBy(0, 1);
			window.scrollBy(0, -1);	
		}, 15);
	}
	
	function increment_type_count() {
		$("#notice_flag").css("display", "inline");
		set_type_count(options.type, store_ref.count + 1);
	}
	
	function set_type_count(type, count) {
		if ( ! type_store[type]) {
		 	return;
		}
		
		type_store[type].count = count;
		
		var tc = type_store[type].counter;
		
		if (tc.lastChild.nodeType == 3) {
			tc.removeChild(tc.lastChild);
		}
		
		if (count == 0) {
			type_store[type].last = '';
			$(tc).hide();
		}
		else {
			tc.innerHTML += '&nbsp;&nbsp;' + count;
			$(tc).show();
			
			repaint_flag();
		}
	}
	
	function open_notice_drawer(type) {
		if (active != type) {
			drawer.find('.notice_texts').hide().end().find('.notice_'+type).show();
			drawer.slideDown('fast', repaint_flag);
			active = type;
			
			if (store_ref) {
				set_active(store_ref.counter);
			}
		}
		
		if (options.close_on_click) {
    		if ( ! drawer.data('close_bound')) {
    			drawer.data('close_bound', true);

    			drawer.click(function() {
    				drawer.one('mouseout', close_notice_drawer);
    			});
    		}		    
		}
	}
	
	function close_notice_drawer() {
		drawer.slideUp('fast', function() {
			drawer.find('.notice_texts').html('');
			$.each(type_store, function(k, v) {
				set_type_count(k, 0);
			});
			
			$("#notice_flag").hide();
			$("#active_notice").attr("id", "");
			
			active = false;
		});
	}
	
	function count_click_handler() {
		var type = this.className.substr(7);
		
		if (type == 'info') {
			return info_callback();
		}
		
		open_notice_drawer(type);
		set_active(this);

		return false;
	}
	
	function set_active(el) {
		$("#active_notice").attr("id", "");
		el.id = "active_notice";
	}
	
})(jQuery);



/* End of file ee_notice.js */
/* Location: ./system/expressionengine/javascript/jquery/plugins/ee_notice.js */