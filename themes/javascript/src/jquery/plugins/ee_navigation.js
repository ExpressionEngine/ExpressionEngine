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
		EE.navigation = {};

	// Common strings to improve compressability
	var ACTIVE = "active",
		HOVER = "hover",
		NAV = "#navigationTabs",
		TICTAC = "first_level",
		PARENT = "parent",

		nav = $(NAV),
		top_level = $(NAV+">li."+PARENT),
		t, 
		current_hovered, 
		moving = false,
	
		max_height = 300;


	// Mouse navigation
	// -----------------------------------------------
	EE.navigation.delay_show_next = function() {
		window.clearTimeout(t);
		moving = true;

		t = window.setTimeout(function() {
			var el = $(current_hovered);
			el.parent().find('.'+ACTIVE+', .'+HOVER).removeClass(ACTIVE).removeClass(HOVER);
			el.addClass(ACTIVE).addClass(HOVER);
		
			EE.navigation.truncate_menus(el.children('ul'));
		
			moving = false;
		}, 60);	// remember, IE timeouts step in 15ms
	};

	EE.navigation.mouse_listen = function() {
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
				EE.navigation.delay_show_next();
			}
		}, function() {
			$(this).removeClass(HOVER);

			// Cache the container to set the height and recall the original height
			var $container = $(this).children('.container');
			$container.height($container.data('original-height'));
		}).find('.'+PARENT+'>a').click(function() {
			return false;
		});
	};

	// Keyboard navigation
	// -----------------------------------------------
	EE.navigation.move_top_level = function(obj, current_li, direction) {
	
		current_li.parents("."+ACTIVE).removeClass(ACTIVE);
		current_li = current_li.closest(NAV+">li");
	
		if (direction && current_li[direction]().length) {
			obj.setFocus(current_li[direction]().children("a"));
		}
	};

	EE.navigation.keyboard_listen = function() {
	
	};
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
				EE.navigation.move_top_level(this, li, "next");
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
					EE.navigation.move_top_level(this, li, "prev");
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
		
			EE.navigation.move_top_level(this, li);
		},
		onBlur: function(event) {
			this.getElements().parent.find('.'+ACTIVE).removeClass(ACTIVE);
		}
	});

	// Menu Truncation
	// -----------------------------------------------
	EE.navigation.truncate_menus = function($menu) {
		var offset        = 151,
			menu_height   = $menu.height(),
			window_height = $(window).height(),
			link_height   = $menu.find('li:first').height(),
			difference    = (offset + menu_height) - window_height;
		
		if (difference > 0) {
			var quantity_to_remove = Math.ceil(difference / link_height) + 3, // Add more to lift it off the bottom
				last_index         = $menu.find('> li.nav_divider:first').index();
		
			$menu.find('> li').slice(last_index - quantity_to_remove, last_index).hide();
		
			$menu.find('> li:eq(' + (last_index - quantity_to_remove - 1) +')').after($('<li />', {
				html: $('<a />', {
					'href': $menu.find('> li > a[href*=tgpref]:first').attr('href'),
					'text': "...view more" // TODO Where should this text come from?
				}),
				mouseover: function() {
					$(this).addClass(HOVER);
				},
				mouseout: function() {
					$(this).removeClass(HOVER);
				}
			}));
		};
	};

	EE.navigation.mouse_listen();
	EE.navigation.keyboard_listen();
});