/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {
/*
Some brainstorming with how yui does accent folding ... maybe in a future iteration.

	var accented = {
		0: /[\u2070\u2080\u24EA\uFF10]/gi,
		1: /[\u00B9\u2081\u2460\uFF11]/gi,
		2: /[\u00B2\u2082\u2461\uFF12]/gi,
		3: /[\u00B3\u2083\u2462\uFF13]/gi,
		4: /[\u2074\u2084\u2463\uFF14]/gi,
		5: /[\u2075\u2085\u2464\uFF15]/gi,
		6: /[\u2076\u2086\u2465\uFF16]/gi,
		7: /[\u2077\u2087\u2466\uFF17]/gi,
		8: /[\u2078\u2088\u2467\uFF18]/gi,
		9: /[\u2079\u2089\u2468\uFF19]/gi,
		a: /[\u00AA\u00E0-\u00E5\u0101\u0103\u0105\u01CE\u01DF\u01E1\u01FB\u0201\u0203\u0227\u1D43\u1E01\u1E9A\u1EA1\u1EA3\u1EA5\u1EA7\u1EA9\u1EAB\u1EAD\u1EAF\u1EB1\u1EB3\u1EB5\u1EB7\u24D0\uFF41]/gi,
		b: /[\u1D47\u1E03\u1E05\u1E07\u24D1\uFF42]/gi,
		c: /[\u00E7\u0107\u0109\u010B\u010D\u1D9C\u1E09\u24D2\uFF43]/gi,
		d: /[\u010F\u1D48\u1E0B\u1E0D\u1E0F\u1E11\u1E13\u217E\u24D3\uFF44]/gi,
		e: /[\u00E8-\u00EB\u0113\u0115\u0117\u0119\u011B\u0205\u0207\u0229\u1D49\u1E15\u1E17\u1E19\u1E1B\u1E1D\u1EB9\u1EBB\u1EBD\u1EBF\u1EC1\u1EC3\u1EC5\u1EC7\u2091\u212F\u24D4\uFF45]/gi,
		f: /[\u1DA0\u1E1F\u24D5\uFF46]/gi,
		g: /[\u011D\u011F\u0121\u0123\u01E7\u01F5\u1D4D\u1E21\u210A\u24D6\uFF47]/gi,
		h: /[\u0125\u021F\u02B0\u1E23\u1E25\u1E27\u1E29\u1E2B\u1E96\u210E\u24D7\uFF48]/gi,
		i: /[\u00EC-\u00EF\u0129\u012B\u012D\u012F\u0133\u01D0\u0209\u020B\u1D62\u1E2D\u1E2F\u1EC9\u1ECB\u2071\u2139\u2170\u24D8\uFF49]/gi,
		j: /[\u0135\u01F0\u02B2\u24D9\u2C7C\uFF4A]/gi,
		k: /[\u0137\u01E9\u1D4F\u1E31\u1E33\u1E35\u24DA\uFF4B]/gi,
		l: /[\u013A\u013C\u013E\u0140\u01C9\u02E1\u1E37\u1E39\u1E3B\u1E3D\u2113\u217C\u24DB\uFF4C]/gi,
		m: /[\u1D50\u1E3F\u1E41\u1E43\u217F\u24DC\uFF4D]/gi,
		n: /[\u00F1\u0144\u0146\u0148\u01F9\u1E45\u1E47\u1E49\u1E4B\u207F\u24DD\uFF4E]/gi,
		o: /[\u00BA\u00F2-\u00F6\u014D\u014F\u0151\u01A1\u01D2\u01EB\u01ED\u020D\u020F\u022B\u022D\u022F\u0231\u1D52\u1E4D\u1E4F\u1E51\u1E53\u1ECD\u1ECF\u1ED1\u1ED3\u1ED5\u1ED7\u1ED9\u1EDB\u1EDD\u1EDF\u1EE1\u1EE3\u2092\u2134\u24DE\uFF4F]/gi,
		p: /[\u1D56\u1E55\u1E57\u24DF\uFF50]/gi,
		q: /[\u02A0\u24E0\uFF51]/gi,
		r: /[\u0155\u0157\u0159\u0211\u0213\u02B3\u1D63\u1E59\u1E5B\u1E5D\u1E5F\u24E1\uFF52]/gi,
		s: /[\u015B\u015D\u015F\u0161\u017F\u0219\u02E2\u1E61\u1E63\u1E65\u1E67\u1E69\u1E9B\u24E2\uFF53]/gi,
		t: /[\u0163\u0165\u021B\u1D57\u1E6B\u1E6D\u1E6F\u1E71\u1E97\u24E3\uFF54]/gi,
		u: /[\u00F9-\u00FC\u0169\u016B\u016D\u016F\u0171\u0173\u01B0\u01D4\u01D6\u01D8\u01DA\u01DC\u0215\u0217\u1D58\u1D64\u1E73\u1E75\u1E77\u1E79\u1E7B\u1EE5\u1EE7\u1EE9\u1EEB\u1EED\u1EEF\u1EF1\u24E4\uFF55]/gi,
		v: /[\u1D5B\u1D65\u1E7D\u1E7F\u2174\u24E5\uFF56]/gi,
		w: /[\u0175\u02B7\u1E81\u1E83\u1E85\u1E87\u1E89\u1E98\u24E6\uFF57]/gi,
		x: /[\u02E3\u1E8B\u1E8D\u2093\u2179\u24E7\uFF58]/gi,
		y: /[\u00FD\u00FF\u0177\u0233\u02B8\u1E8F\u1E99\u1EF3\u1EF5\u1EF7\u1EF9\u24E8\uFF59]/gi,
		z: /[\u017A\u017C\u017E\u1DBB\u1E91\u1E93\u1E95\u24E9\uFF5A]/gi
	};

	word = "üBer";

	_.each(accented, function(reg, letter) {
		word = word.replace(reg, letter)
	});
*/




	/**
	 * Relationship Class
	 *
	 * This is not public, you must use EE.setup_relationship_field
	 * to instantiate it. Handles all of the progressive enhancement
	 * on the relationship cp frontend.
	 *
	 * The constructor does most of the precaching before handing
	 * off to the class methods for interaction related things.
	 */
	function RelationshipField(container, empty) {
		this.force_empty = !! empty;

		// three main components per field
		this.root = $(container).find('.multiselect');
		this.active = $(container).find('.multiselect-active');
		this.searchField = $(container).find('.multiselect-filter input');

		// not a multi field - we could catch this in the php, but this works
		// and we may want a prettier single relationship interface at some point
		if ( ! this.root.length) {
			return;
		}

		// cache a few things for search and query-less access
		this.activeMap = {};
		this.listItems = this.root.find('li');
		this.cache = _.map(this.root.find('label'), function(el, i) {
			return $(el).text();
		});

		// create a templating function
		this.createItem = _.template(
			this.active.data('template')
		);

		// map indices to list items
		this.defaultList = _.object(
			_.range(this.listItems.length),
			_.map(this.listItems, $) // [$(n1), $(n2), ...]
		);

		// and off we go
		this.init();
	}

	/**
	 * RelationshipField class methods
	 */
	RelationshipField.prototype = {

		/**
		 * Secondary setup code
		 */
		init: function() {
			// visuals
			this._checkScrollBars();
			this._disallowClickSelection();

			// linked list interactions
			this._bindSelectToClick();
			this._bindDeselectToRemove();
			this._bindAddActiveOnSelect();
			this._bindScrollToActiveClick();
			this._bindSortable();
			this._bindSubmitClear();

			// filtering
			this._setupFilter();
		},

		/**
		 * Check the scrollbars on our two elements and remove the
		 * forced scrollbar if the container is not overflowing.
		 *
		 * Due to a behavioral bug in safari we cannot create a check
		 * for if we need scrollbars only if we do not. If you add scrollbars
		 * to an already overflowing item in safari, it will fall back to the
		 * invisible overlay scrollbar on OS X > Lion. So the solution is to
		 * add the scrollbars before any dom changes, and then call this to
		 * remove them again if the dom changes did not cause an overflow.
		 */
		_checkScrollBars: function() {
			if (this.root.prop('scrollHeight') <= this.root.prop('clientHeight')) {
				this.root.removeClass('force-scroll');
			}

			if (this.active.prop('scrollHeight') <= this.active.prop('clientHeight')) {
				this.active.removeClass('force-scroll');
			}
		},

		/**
		 * Toggle the hidden checkbox and active class when an item in the
		 * left list is clicked. Always refocus the search box for quick
		 * consecutive filtering.
		 */
		_bindSelectToClick: function() {
			var that = this;

			this.root.on('click', 'li', function(evt) {
				evt.preventDefault();

				var box = $(this).find(':checkbox');
				wasChecked = box.is(':checked');

				$(this).toggleClass('selected', ! wasChecked);
				box.attr('checked', ! wasChecked);

				// refocus the search after event bubbles
				_.defer($.proxy(that.searchField, 'focus'));
			});
		},

		/**
		 * When hitting the X on an item in the right list, we want to
		 * remove it and then trigger a click on the corresponding left
		 * hand list item to cleanly deselect it.
		 */
		_bindDeselectToRemove: function() {
			var that = this;

			this.active.on('click', '.remove-item', function() {
				var idx = that._index(this);
				that.listItems.eq(idx).trigger('click');
				return false;
			});
		},

		/**
		 * Clicking on an item in the right hand side list should force
		 * the corresponding item on the left hand list to scroll into view.
		 */
		_bindScrollToActiveClick: function() {
			var that = this;

			this.active.on('click', 'li', function() {
				var idx = that._index(this),
					scrollTo = that.listItems.eq(idx),
					topOfScrollOffset;

				topOfScrollOffset = that.root.offset().top - that.root.scrollTop();

				// We're dealing with offsets relative to the document, so to
				// get an absolute scroll position we compare these offsets.
				that.root.animate({
					scrollTop: scrollTo.offset().top - topOfScrollOffset
				});
			});
		},

		/**
		 * Selecting an item on the left hand side should create a sortable
		 * proxy of said item at the bottom of the right hand list. Deselecting
		 * an item on the left, should remove it from the right.
		 */
		_bindAddActiveOnSelect: function() {
			var that = this,
				listItems = this.listItems,
				util;

			// Utility methods for selecting and deselecting
			util = {
				activeLength: 0,

				moveOver: function(i) {
					var newLi = $(
						that.createItem({
							title: that.cache[i]
						})
					);

					newLi.data('list-index', i);
					that.active.find('ul').append(newLi);

					that.activeMap[i] = newLi;
					this.activeLength++;
					that.defaultList[i].find('input:text').val(this.activeLength);
				},

				moveBack: function(i) {
					var old_value = that.defaultList[i].find('input:text').val();

					if (old_value < this.activeLength) {
						var li = that.activeMap[i],
							idx = li.index() + 1,
							reSort = li.nextAll();

						reSort.each(function() {
							that.defaultList[ that._index(this) ].find('input:text').val(idx++);
						});
					}

					this.activeLength--;
					that.defaultList[i].find('input:text').val(0);
					that.activeMap[i].remove();
					delete that.activeMap[i];
				}
			};


			// Move over existing ones

			// Webkit won't use the custom scroll bar if you overflow before
			// adding the class. So we add the class and remove it if it's not
			// overflowing. Silly browsers.
			that.active.addClass('force-scroll');

			if ( ! that.force_empty) {
				// find existing checked items
				var checked = _.map(this.root.find(':checked'), function(el, i) {
					var li = $(el).closest('li'),
						text = li.find('input:text');

					return [li, +text.val()]; // (cons item int_text)
				});

				// sort them by their order field
				checked = _.sortBy(checked, function(el) {
					return el[1];
				});

				// move them over in the correct order
				_.each(checked, function(el, i) {
					var li = el[0],
						idx = that.listItems.index(li);

					util.moveOver(idx);
				});

			} else {
				_.each(this.root.find(':checked'), function(el, i) {
					var parent = $(el).closest('li');

					parent.removeClass('selected');
					parent.find('input:text').val(0);
					el.removeAttribute('checked');

				});
			}

			that._checkScrollBars();


			// bind the select event
			this.root.on('click.moveover', 'li', function(evt) {

				// Webkit won't use the custom scroll bar if you overflow before
				// adding the class. So we add the class and remove it if it's not
				// overflowing. Silly browsers.
				that.active.addClass('force-scroll');

				var box = $(this).find(':checkbox'),
					idx = that.listItems.index(this);

				if ( ! box.is(':checked')) {
					util.moveBack(idx);
				} else {
					util.moveOver(idx);
				}

				that._checkScrollBars();
			});
		},

		/**
	 	 * Clear unused sorting data from post before submit so that we don't
	 	 * overwhelm the POST array with too many variables.
	 	 */
		_bindSubmitClear: function() {
			var that = this;

			this.root.parents('form').on('submit', function(evt) {

				that.root.find('input:text').each(function() {
					if ($(this).val() == "0") {
						$(this).remove();
					}
				});
				return true;
			});

		},

		/**
		 * Sorting the right list should update the hidden textareas in the
		 * left list so that they display the relative sort.
		 */
		_bindSortable: function() {
			var that = this,
				previousPosition,
				getOrder, start, update;


			getOrder = function(el) {
				return +that.defaultList[ that._index(el) ].find('input:text').val();
			};

			start = function(evt, ui) {
				previousPosition = getOrder(ui.item);
			};

			update = function(evt, ui) {
				var li = ui.item,
					newPosition = li.index() + 1,
					reSort, i;

				if (newPosition == previousPosition) {
					return;
				}

				// we don't need to process the entire list, only the subset
				// that we disturbed.
				if (newPosition < previousPosition) {
					i = newPosition;
					reSort = li.nextAll().andSelf(); //.addBack();
				} else {
					i = 1; // in theory we can start at previous, but then our numbers slowly get bigger
					reSort = li.prevAll().andSelf(); // .addBack();
				}

				reSort.each(function() {
					that.defaultList[ that._index(this) ].find('input:text').val(i++);
				});
			};

			// hookup sortable
			this.active.find('ul').sortable({
				axis: 'y',
				start: start,
				update: update
			});
		},

		/**
		 * Utility method to find the left-list-index for any item
		 * in the right list.
		 */
		_index: function(item) {
			return $(item).closest('li').data('list-index');
		},

		/**
		 * Bind an ee_interact event to start the filtering.
		 * Throttle it slightly to avoid taking down the browser
		 * on very long lists.
		 */
		_setupFilter: function() {

			var ul = this.root.find('ul');

			this.searchField.keydown(function(evt) {
				if (evt.keyCode == 13) {
					evt.preventDefault();
				}
			});

			this.searchField.on(
				'interact',
				_.debounce(
					$.proxy(this, '_filterResults', this.defaultList, ul),
					100
				)
			);
		},

		/**
		 * Handle the filtering event step by step.
		 *
		 * 1) Grab the search text
		 * 2) Score it agains the cached texts
		 * 3) Hide any that have a score of 0
		 * 4) Sort them by score
		 */
		_filterResults: function(defaultList, ul, evt) {

			// Webkit won't use the custom scroll bar if you overflow before
			// adding the class. So we add the class and remove it if it's not
			// overflowing. Silly browsers.
			this.root.addClass('force-scroll');

			// User input and the node we're working with
			var search = evt.target.value.toLowerCase(),
				searchLength = search.length;

			// We take the element off the dom temporarily for processing.
			// This vastly improves performance at > 500 items.
			// Normally that makes perfect sense, but I must admit in
			// this case it's a little strange, since we move them off-dom
			// individually to reorder them. Something about not forcing
			// repaints every time? Not 100% sure, but this works, so it's
			// staying.
			ul.find('li').detach();


			// no search, show all, use default order
			if (searchLength == 0) {
				_.each(defaultList, function(el) {
					el[0].style.display = '';
				});

				this._insertInOrder(ul, defaultList);
				return this._checkScrollBars();
			}

			// compute a score for each item in the list
			var scores = _.map(
				this.cache,
				_.partial(this._scoreString, search)
			);

			// Manually hide and unhide. Could be prettier, but can't be quicker.
			_.each(defaultList, function(el, i) {
				if (scores[i] === 0) {
					el[0].style.display = 'none';
				} else {
					el[0].style.display = '';
				}
			});

			// Create an array of numbers from 0 to n, where n
			// is the total number of items. The numbers shall
			// be sorted in the desired final sorting order.
			var order = _.sortBy(
				_.range(this.cache.length),
				function(i) { return -scores[i]; }
			);

			// Move li's to the desired positions
			this._insertInOrder(ul, defaultList, order);

			// And finally show hide the scroll bar
			this._checkScrollBars();
		},

		/**
		 * A cutesy attempt at best-match fuzzy matching. The fuzzy
		 * matching part can actually be done quite simply with some
		 * regex (see commented out portion at bottom of function).
		 * The problem is that those results aren't the most natural
		 * unless you can order them logically. This code tries to
		 * do just that.
		 */
		_scoreString: function(search, item) {
			var score = 0,
				letterOffset = 1,
				searchLength = search.length;

			item = item.toLowerCase();

			// First letter match is an big plus
			if (item[0] == search[0]) {
				score += 1;
			}

			for (var i = 0; i < searchLength; i++) {
				var charLoc = item.indexOf(
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
				item = item.substr(charLoc + 1);
			}

			// Score per letter * letter per item letter looked at
			return (score / searchLength) * (searchLength / letterOffset);


			/* Alternative matching algorithm:

			// Does fuzzy match but keeps the list alphabetic
			// much easier implementation but doesn't always return
			// the most intuitive result.

			var textMatch = evt.target.value.replace(/\W/, '').split('').join('\\w*'),
				reg = new RegExp(textMatch, 'i'),
				that = this;

			var matches = _.filter(this.cache, function(el, i) {
				var show = !! el.replace(/\W/, '').match(reg);
				that.root.find('li').eq(i).toggle(show);
				return show;
			});

			*/
		},

		/**
		 * Takes a numerically indexed object of items and an array
		 * of integers that represent the order. It then inserts the
		 * items from the object into the given parent in the order that
		 * the array specifies.
		 *
		 * items = {1:red, 2:blue, 3:orange}
		 * order = [2,3,1]
		 *
		 * Parent after method:
		 *		blue
		 *		orange
		 *		red
		 */
		_insertInOrder: function(parent, items, order) {

			if ( ! order) {
				order = _.range(_.size(items));
			}

			// I know it's tempting to do this with a simple jquery append
			// on a dummy object. Don't do it - it's sloooow.

			var dummy = document.createElement('ul');

			_.each(order, function(i) {
				dummy.appendChild(items[i][0]);
			});

			var children = _.toArray(dummy.childNodes),
				childLength = children.length,
				i = 0;

			// Performance tweak, to make it feel more responsive. Appending,
			// even as a documentFragment, requires a lot of style calculations
			// that block the rendering process. Since the user never sees more
			// than ~20 items, we'll do the first 100 immediately and then add
			// the others piecemeal in steps of 100.

			(function batch() {
				parent.append(children.slice(i, 100 + i));
				i += 100;

				if (i < childLength) {
					_.defer(batch);
				}
			})();
		},


		/**
		 * Quick clicking can sometimes lead to double and triple
		 * click selections. If we think that might have happened
		 * we'll simply remove them.
		 */
		_disallowClickSelection: function() {
			var cnt = 0,
				that = this;

			this.root
				.dblclick(that._deselect)
				.click(function() {
					cnt++;
					_.debounce(function() {
						cnt = 0;
					}, 500);

					if (cnt >= 2) {
						that._deselect();
					}
				}
			);
		},

		/**
		 * Utility method to remove the active selection
		 */
		_deselect: function() {
			// Aren't you glad we wrote that rte and speak fluent range

			if (window.getSelection) {
				window.getSelection().removeAllRanges();
			} else if (document.selection) {
				document.selection.empty();
			}
		}
	};

	/**
	 * Cache the relationship object on the associated dom element
	 *
	 * Makes sure that we only ever instantiate a relationship of a
	 * given name once.
	 */
	function cached_relationship_object(element, creation_callback) {
		var el = $(element);

		if ( ! el.data('relationship-object')) {
			el.data('relationship-object', creation_callback(el));
		}

		return el.data('relationship-object');
	}

	/**
	 * Public method to instantiate
	 *
	 * If it's a relationship field we need to find the cells for existing
	 * fields and also setup the grid binding for new rows. Otherwise we
	 * simply bind on the field name we were given.
	 */
	EE.setup_relationship_field = function(field_name) {
		var element = document.getElementById('relationship-' + field_name);

		return cached_relationship_object(element, function(el) {
			return new RelationshipField(el);
		});
	};

	Grid.bind('relationship', 'display', function(cell) {
		var element = cell.find('.relationship');

		return cached_relationship_object(element, function(el) {
			return new RelationshipField(cell, ! cell.data('row-id'));
		});
	});

})(jQuery);
