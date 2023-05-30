/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

/**
 * A class to fuzzy search on a list:
 *
 * options.keep - selector of items that are always visible
 *
 * fuzzy = new FuzzyListSearch($('ul'), {keep: 'selector'});
 * fuzzy.filter('cho'); // will match "choice", "cathode", "bachelor"
 *
 */
function FuzzyListSearch(ul, options) {
	this.ul = ul;
	this.keep = $();

	var lis = ul.find('li, > a');

	if (options.keep) {
		this.keep = this.ul.find(options.keep);
		lis = lis.not(options.keep);
	}

	this.items = lis.map(function(index) {
		return {
			el: this,
			text: $(this).text(),
			score: 1,
			origIndex: index
		}
	});

	this.length = this.items.length;
}

FuzzyListSearch.prototype = {

	/**
	 * Score each item based on search, reorder by scores, and hide
	 * any with a score of 0
	 */
	filter: function(search) {

		if (search == '') {
			return this.reset();
		}

		var length = 0,
			that = this;

		// update the score and show/hide
		_.each(this.items, function(item) {
			if ($(item.el).hasClass('hidden')) {
				return
			}
			item.score = that._scoreString(item.text, search);
			$(item.el).toggle(item.score != 0);
			length += Math.ceil(item.score);
		});

		this.length = length;
		this.items.sort(function(a, b) { return b.score - a.score; });
		this._update();
	},

	/**
	 * Show the entire list
	 */
	reset: function() {
		_.each(this.items, function(item) {
			if ($(item.el).hasClass('hidden')) {
				return
			}
			$(item.el).toggle(true);
		});

		this.length = this.items.length;
		this.items.sort(function(a, b) { return a.origIndex - b.origIndex; });
		this._update();
	},

	/**
	 * Re-render the list, make sure keep elements are always there
	 */
	_update: function() {
		this.ul.empty().append(_.pluck(this.items, 'el'));
		this.ul.append(this.keep);
	},

	/**
	 * Rough fuzzy matching scorer
	 */
	_scoreString: function(text, search) {
		var score = 0,
			letterOffset = 1,
			searchLength = search.length;

		text = text.toLowerCase();

		// First letter match is a big plus
		if (text[0] == search[0]) {
			score += 1;
		}

		for (var i = 0; i < searchLength; i++) {
			var charLoc = text.indexOf(
				search.charAt(i).toLowerCase()
			);

			switch (charLoc) {
				case -1: return 0;				// not found, not our word
				case  0: score += 0.6;			// first position, good
					if (i == letterOffset)		// consecutive, better
						score += 0.4;
					break;
				default: score += 0.4 / letterOffset	//  scaled by how close it was
			}

			letterOffset += charLoc;
			text = text.substr(charLoc + 1);
		}

		// Score per letter * letter per item letter looked at
		return (score / searchLength) * (searchLength / letterOffset);
	}
}

/**
 * A helper class to handle moving the .act class up and down through
 * a list that is potentially reordered and has hidden elements.
 */
function ListFocus(ul) {
	this.ul = ul;
	this.items = ul.find('li, > a');
	this.isNav = !! ul.closest('.nav-main').length;

	this.scrollWrap = this.getScrollWrap();
	this.scrollOffset = this.isNav ? 7 : 4;

	this.current = -1;
	this.scrolled = 0;

	this.setLength(this.items);
}

ListFocus.prototype = {

	getScrollWrap: function() {
		if (this.isNav) {
			return this.ul;
		}

		return this.ul.closest('.scroll-wrap, .filter-submenu__scroll');
	},

	/**
	 * Set the focus index
	 */
	setCurrent: function(index) {
		this.current = index;
		this.ul.find('li a.act').removeClass('act');
		if (index < 0) {
			this.active = null;
			return;
		}

		this.active = this.ul.find('li a:visible').eq(index);
		this.active.addClass('act');
		this._updateScroll();
	},

	/**
	 * Get the focused element
	 */
	getCurrent: function() {
		return this.active;
	},

	/**
	 * Update the known list length
	 */
	setLength: function(length) {
		this.length = length;
	},

	/**
	 * Make sure the active element is visible
	 */
	_updateScroll: function() {
		var delta = this.current - this.scrolled;

		if (delta > this.scrollOffset) {
			this.scrolled += delta - this.scrollOffset;
		} else if (delta <= 0) {
			this.scrolled += delta;
		}

		this.scrollWrap.scrollTop(this.scrolled * this.active.outerHeight());
	},

	/**
	 * Move the focus down one element if possible
	 */
	down: function() {
		this.setCurrent(Math.min(this.length, this.current + 1));
	},

	/**
	 * Move the focus up one element if possible
	 */
	up: function() {
		this.setCurrent(Math.max(0, this.current - 1));
	}
};

/**
 * And now the glue code. Given the <input> element on one of our
 * filter lists, fuzzy search through the list and allow the user to
 * arrow through it to select an element.
 */

var keys = { 'enter': 13, 'escape': 27, 'up': 38, 'right': 39, 'down': 40 };

$.fn.fuzzyFilter = function() {

	return this.each(function() {

		if ($(this).data('fuzzyFilterActive')) {
			return;
		}

		$(this).data('fuzzyFilterActive', true);

		var input = $(this);
		var list = $(this).closest('.sub-menu, .nav-sub-menu, .filter-submenu, .dropdown')
			.find('ul, .filter-submenu__scroll, .dropdown__scroll');

		var focusBar = new ListFocus(list);
		var fuzzyList = new FuzzyListSearch(list, {
			keep: ':has(.add, .nav-add)'
		});

		var scrollWrap = focusBar.getScrollWrap();

		// the input gains focus when it becomes visible. at this point
		// we want to make sure that our menu isn't going to shrink horizontally
		// as longer words are filtered out.
		input.on('focus', function() {
			scrollWrap.width(scrollWrap.width());
		});

		input.on('keydown', function(evt) {
			focusBar.setLength(fuzzyList.length);

			switch (evt.keyCode)
			{
				case keys.enter:
					evt.preventDefault();

					focusBar.getCurrent()[0].click();
					break;
				case keys.escape:
					input.val('');
					break;
				case keys.up:
					focusBar.up();
					break;
				case keys.down:
					focusBar.down();
					break;
				default:
					return;
			}

			evt.preventDefault();
		});

		input.on('interact', function() {
			fuzzyList.filter(input.val());
			focusBar.setCurrent(0);
		});
	});
};

$.fuzzyFilter = function() {
	$('.nav-filter input, input[data-fuzzy-filter=true]').fuzzyFilter();
};

// and create the defaults, third parties can call this to "refresh"
$.fuzzyFilter();

})(jQuery);
