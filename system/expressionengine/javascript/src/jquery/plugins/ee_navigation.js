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
 * ExpressionEngine Navigation Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

jQuery(document).ready(function() {

var $ = jQuery;

// Common strings to improve compressability
var ACTIVE = "active",
	HOVER = "hover",
	NAV = "#navigationTabs",
	TICTAC = "first_level",
	PARENT = "parent",

	nav = $(NAV),
	top_level = $(NAV+">li."+PARENT),
	t, current_hovered, moving = false;


// Mouse navigation
// -----------------------------------------------

function delay_show_next() {

	if ( ! current_hovered) {
		var el = $(current_hovered);
		el.parent().find('.'+ACTIVE+', .'+HOVER).removeClass(ACTIVE).removeClass(HOVER);
		return el.addClass(ACTIVE).addClass(HOVER);
	}
	
	window.clearTimeout(t);
	moving = true;

	t = window.setTimeout(function() {
		var el = $(current_hovered);
		el.parent().find('.'+ACTIVE+', .'+HOVER).removeClass(ACTIVE).removeClass(HOVER);
		el.addClass(ACTIVE).addClass(HOVER);
		
		moving = false;
	}, 60);	// remember, IE timeouts step in 15ms
}

// Mouse leaves nav - close all
nav.mouseleave(function() {
	nav.find('.'+ACTIVE).removeClass(ACTIVE);
});

// Move along the main menu - each should open in turn
top_level.mouseenter(function() {
	if (nav.find('.'+ACTIVE).length) {
		nav.find('.'+ACTIVE).removeClass(ACTIVE);
		$(this).addClass(ACTIVE);
	}
});

// Toggle menu open / closed
top_level.find("a."+TICTAC).click(function() {
	var el = $(this).parent();
	
	if (el.hasClass(ACTIVE)) {
		el.removeClass(ACTIVE);
	}
	else {
		el.addClass(ACTIVE);
	}
	return false;
});

// Small delay in showing or hiding submenus to make mouse navigation smoother
top_level.find("ul li").hover(function() {
	current_hovered = this;
	
	if ( ! moving) {
		delay_show_next();
	}
}, function() {
	$(this).removeClass(HOVER);
}).find('.'+PARENT+'>a').click(function() {
	return false;
});


// Keyboard navigation
// -----------------------------------------------

function move_top_level(obj, current_li, direction) {
	
	current_li.parents("."+ACTIVE).removeClass(ACTIVE);
	current_li = current_li.closest(NAV+">li");
	
	if (direction && current_li[direction]().length) {
		obj.setFocus(current_li[direction]().children("a"));
	}
}

nav.ee_focus("a."+TICTAC, {
	removeTabs: "a",	
	onEnter: function(event) {
		var target = $(event.target),
			li = target.parent();

		if (li.hasClass(PARENT)) {
			li.addClass(ACTIVE);
			this.setFocus(li.find("ul>li>a").eq(0));
		}
	},
	onRight: function(event) {
		var target = $(event.target),
			li = target.parent();

		if (li.hasClass(PARENT) && ! target.hasClass(TICTAC)) {
			li.addClass(ACTIVE);
			this.setFocus(li.find("ul>li>a").eq(0));
		}
		else {
			move_top_level(this, li, "next");
		}
	},
	onLeft: function(event) {
		var target = $(event.target),
			li = target.parent();
		
		if (target.hasClass(TICTAC) && li.prev().length) {
			this.setFocus(li.prev().children("a"));
		}
		else {
			li = li.parent().closest("."+PARENT);
			li.removeClass(ACTIVE);
			
			if (li.children("a."+TICTAC).length) {
				move_top_level(this, li, "prev");
			}
			else {
				this.setFocus(li.children("a").eq(0));
			}
		}
	},
	onUp: function(event) {
		var target = $(event.target),
			li = target.parent(),
			prev = li.prevAll(":not(.nav_divider)");
		
		if ( ! target.hasClass(TICTAC) && li.prev.length) {
			this.setFocus(prev.eq(0).children("a"));
		}
	},
	onDown: function(event) {
		var target = $(event.target),
			li = target.parent(),
			next = li.nextAll(":not(.nav_divider)");

		if ( ! target.hasClass(TICTAC) && next.length) {
			this.setFocus(next.eq(0).children("a"));
		}
		else if (li.hasClass(PARENT)) {
			li.addClass(ACTIVE);
			this.setFocus(li.find("ul>li>a").eq(0));
		}
	},
	onEscape: function(event) {
		var target = $(event.target),
			li = target.parent();
		
		move_top_level(this, li);
	},
	onBlur: function(event) {
		this.getElements().parent.find('.'+ACTIVE).removeClass(ACTIVE);
	}
});

});