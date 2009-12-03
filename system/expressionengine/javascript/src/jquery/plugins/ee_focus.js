/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/*!
 * ExpressionEngine Focus Manager Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

// Create a focusin and focusout event. Normally focus and blur don't bubble.

jQuery.each({'focus' : 'FocusIn', 'blur': 'FocusOut'}, function(name, domEvent) {
	var bind_name = domEvent.toLowerCase();
	
	function handler(event, data) {
		var el = this;
		
		jQuery.each(jQuery.data(this, 'events')[bind_name] || [], function(i, fn) {
			fn.call(el, event, data);
		});
	}
	
	jQuery.event.special[bind_name] = {
		setup: function(data, namespaces) {
			
			// Gecko doesn't claim support for focusin/out event and it doesn't look like they
			// ever will ( https://bugzilla.mozilla.org/show_bug.cgi?id=396927 ).
			// In practice however, it seems to not matter one bit - in fact, using the event listener
			// in the capturing phase seems to only work in Safari, but breaks FF 3.5 and Opera.
			// so we might need to branch for FF 3.0 .... @todo get 3.0 and test
			
			// if ($.browser.firefox) {
			// 	this.addEventListener(name, handler, true);
			// }
			// else {
				jQuery.event.add(this, ($.browser.msie ? bind_name : 'DOM'+domEvent), handler);
			// }
		},
		teardown: function(namespaces) {
			// @todo namespaces?
			
			return false;
		}
	};
});

EE.tabQueue = (function($) {
	
	var queue = {},
		parents = [];
	
	$(document).bind('focusin', function(event) {
		
		var event_parent = $(event.target).closest(parents.join(',')),
			ignore;

		// Ugly variable reuse
		if (event_parent.length && (event_parent = event_parent.eq(0).data('focusmanager'))) {
			ignore = $.data(event_parent);
		}
		
		$.each(queue, function(key, obj) {
			if (ignore != key) {
				var opt = obj.getOptions();
				opt.onBlur.call(obj, event);
			}
		});
	});
	
	return {
		append: function(selector) {

			var parent = $(selector).data('focusmanager'),
			id = $.data(parent);
			
			if (queue[id]) {
				delete queue.id;
			}
			
			queue[id] = parent;
			parents.push(selector);
		},
		prepend: function(el) {
			// @todo how in the world does this work? create new and copy over? useless language
		}
	};
	
})(jQuery);


(function($) {

	var key_callbacks = {},
		focus_defaults = {
			circular: false,
			focusClass: 'focused',
			onBlur: function() {}
		};

	// Connect keycodes to callback names
	
	$.each(['Left', 'Right', 'Up', 'Down', 'Escape', 'Enter'], function(i, el) {
		key_callbacks[$.ui.keyCode[el.toUpperCase()]] = 'on'+el;
		focus_defaults['on'+el] = function() {};
	});
	
	// @todo
	// Global
	// Current Focus
	// Focus Chain (menu, 2nd nav, maincontent, accessories, sidebar, footer)
	// Focus Event - check current focus parent
	//		res = $(event.target).closest(/*all parent selectors*/);
	// 		res.eq(0).data('focusmanager')

	// var focus_event = $(...lalala...)
	// EE.tabQueue.insertAfter(someparent (not necessarily menu parent))

	function EE_Focus(elements, selectors, options) { 
	
		var that = elements.parent,
			obj = this;
		
		if (options.removeTabs) {
			that.find(options.removeTabs).attr("tabIndex", -1);
		}
		
		elements.children.attr("tabIndex", -1).eq(0).attr("tabIndex", 0);

		that.bind('keydown', function(event) {

			// Prevent Scrolling
			if (event.keyCode > 36 && event.keyCode < 41) {
				event.preventDefault();
			}
			
			if (key_callbacks[event.keyCode]) {
				var ret = options[key_callbacks[event.keyCode]].call(obj, event);

				if (ret === true) {
					$(event.target).trigger("click");
				}

				return ret;
			}
		});
		
	
		$.extend(this, {
			
			getElements: function() {
				return elements;
			},
			getSelectors: function() {
				return selectors;
			},
			getOptions: function() {
				return options;
			},
			setFocus: function(el) {
				// @todo should affect all global instances
				// blur current selection, change tab index
				// and all that jazz
				
				$('.'+options.focusClass).removeClass(options.focusClass);
				el.addClass(options.focusClass);
				el.focus();
			}
		});
	}
	
	
	$.fn.ee_focus = function(child_selector, params) {
				
		var el, defaults, selectors;
		
		if (el = this.eq(0).data('focusmanager')) {
			return el;
		}
		
		selectors = {
			parent: $(this).selector,
			children: child_selector
		};
		
		defaults = $.extend({}, focus_defaults);
		params = $.extend(defaults, params);
		
		this.each(function(i) {
			var elements = {
				parent: $(this),
				children: $(this).find(selectors.children)
			};

			el = new EE_Focus(elements, selectors, params);
			elements.parent.data('focusmanager', el);
		});
		
		EE.tabQueue.append(selectors.parent);
		return el;
	}

})(jQuery);