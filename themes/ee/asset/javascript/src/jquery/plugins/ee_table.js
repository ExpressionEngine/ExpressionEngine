/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";


		// @todo @pk ideas -------------

		// ensure copyright notice on all files (I always forget ...)

		// edit specific:

		// add base64 thing to "add" tab link to "save" the search
		//		- need global method to manipulate it first
		// clear search link (redirect to base path)
		// add sort to "return to filtered entries"? (feature)


		// TODO:

		// sorting on single page, should just need to set up with add_data
		// make sure that all of this works with multiple tables on the page (it should)
		// make keyup timeout configurable
		// flip headerSortUp and down in the css, is silly

		// /@todo @pk todo/ideas -------------

$.widget('ee.table', {

	_listening: $(),		// form elements the filter is listening to
	_template_string: '',	// raw template
	_current_data: {},		// current_data

	options: {
		uniqid: null,		// uniqid of related elements
		base_url: null,		// url for requests

		pagination: null,	// element(s)

		template: null,		// table template
		pag_template: null,	// pagination template

		rows: [],			// initial row data
		sort: [],			// [[column, desc], [column2, asc]]
		columns: [],		// column names to match_data / search, filter on the spot

		cache_limit: 600,	// number of items, not pages!

		filters: {},		// map of active filters {"field": "bar"}, usually updated right before request is made

		cssAsc: 'headerSortDown',	// matches tablesorter.js
		cssDesc: 'headerSortUp'
	},

	// jQuery ui widget constructor
	_create: function() {

		var self = this,
			options = self.options;

		// cache content parent and no results
		self.tbody = self.element.find('tbody');

		if ( ! self.tbody.length) {
			self.tbody = $('<tbody/>');
			self.element.append(self.tbody);
		}

		self.no_results = $('<div />').html(options.no_results);

		// check if we need no results to begin with
		if ( ! self.tbody.children().length) {
			self.element.hide();
			self.element.after(self.no_results);
		}

		// set defaults
		self.filters = options.filters;

		// fix ampersands
		options.base_url = options.base_url.replace(new RegExp('&amp;', 'g'), '&');

		// setup dependencies
		self.sort = new Sort(options, self);
		self.cache = new Cache(options.cache_limit);
		self.pagination = new Pagination(options, self);

		// cache initial data
		var cache_id = self._prep_for_cache(),
			cache_data = {
				'html_rows': self.tbody.find('tr'),
				'pagination': self.pagination.html(),
				'rows': options.rows
			};

		self.cache.set(cache_id, cache_data);
		self._current_data = cache_data;

		// create unique template name and compile
		self.template_id = options.uniqid + '_row_template';
		self.set_template(options.template);

		// bind create event (@todo consider ditching, pretty much impossible to bind on)
		self._trigger('create', null, self._ui( { data: cache_data } ));
	},

	/**
	 * Get container (tbody)
	 */
	get_container: function() {
		return this.tbody;
	},

	/**
	 * Set container
	 */
	set_container: function(el) {
		this.tbody = $(el);
	},

	/**
	 * Get header element by short name
	 */
	get_header: function(name) {
		self.element.find('th').filter(function() {
			return ($(this).data('table_column') == name);
		});
	},

	/**
	 * Get raw template string
	 */
	get_template: function() {
		return this._template_string;
	},

	/**
	 * Use a different template
	 */
	set_template: function(template) {
		this._template_string = template;
		$.template(this.template_id, template);
	},

	/**
	 * Get current cache
	 */
	get_current_data: function() {
		return this._current_data;
	},

	/**
	 * Clear all caches
	 */
	clear_cache: function() {
		this.cache.clear();
		return this;
	},

	/**
	 * Unbind all filters
	 */
	clear_filters: function() {
		// @todo reset form content?

		this.filters = {};
		this._listening.each(function() {
			$(this).unbind('interact.ee_table');
		});
		return this;
	},

	/**
	 * Reset sort to initial conditions
	 */
	clear_sort: function() {
		// @todo fire sort events
		this.sort.reset();
		return this;
	},

	/**
	 * Add a filter
	 *
	 * Can be a form or a regular object
	 */
	add_filter: function(obj) {
		var self = this,
			url = EE.BASE + '&' + self.options.base_url;

		// add to filters and update right away
		// @todo do not hardcode url!
		if ($.isPlainObject(obj)) {
			self._set_filter(self._listening);
			self.filters = $.extend(self.filters, obj);
			self._request(url);
			return this;
		}

		var form = obj.closest('form'),
			evts = 'interact.ee_table',
			_timeout;

		if (form && obj.is(form)) {
			// bind to submit only if it's a form
			evts += ' submit.ee_table';
		} else {
			// A filter outside of a form? We most likely don't want enter to
			// do anything. This was happening in the file modal search box
			obj.bind('keydown', function(e) {
				if (e.keyCode == 13) {
					e.preventDefault();
				}
			});
		}

		$(obj).bind(evts, function(e) {

			// @todo only timeout on some inputs? (textareas)

			clearTimeout(_timeout);
			_timeout = setTimeout(function() {
				self._set_filter(self._listening);
				self._request(url);
			}, 200);

			return false;
		});

		self._listening = self._listening.add(obj);
		self._set_filter(self._listening);

		return this;
	},


	/**
	 * Set sort (see sort::set for info)
	 */
	set_sort: function(column, dir) {
		this.sort.set(column, dir);
		return this;
	},

	/**
	 * Add sort (see sort::add for info)
	 */
	add_sort: function(column, dir) {
		this.sort.add(column, dir);
		return this;
	},

	/**
	 * Refresh with current filters and sort intact
	 */
	refresh: function() {
		var url = EE.BASE + '&' + this.options.base_url;

		this._request(url);
		return this;
	},

	/**
	 * Make a request with the current filters and sort
	 *
	 * Updates the main table, pagination, caches, and triggers
	 * the load and update events.
	 */
	_request: function(url) {
		var self = this,
			data, success;

		self._trigger('load', null, self._ui(/* @todo args */));

		// A cache hit and an ajax result below are both
		// considered successes and will call this with
		// the correct data =)
		success = function(res) {
			self._current_data = res;

			// @todo only remove those that are not in the result set?
			if ( ! res.rows.length) {
				if (self.tbody.is('tbody')) {
					self.tbody.empty();
					self.element.hide();
					self.element.after(self.no_results);
				} else {
					self.tbody.html(self.no_results);
				}
			} else {
				self.element.show();
				self.tbody.html(res.html_rows);
				self.no_results.remove();
			}

			self.pagination.update(res.pagination);
			self._trigger('update', null, self._ui( {data: res} ));
		};

		var cache_id = self._prep_for_cache();

		// Do we have this page cached?
		data = self.cache.get(cache_id);
		if (data !== null) {
			return success(data);
		}

		// The pagination library reads from get, so we need
		// to move tbl_offset. Doing it down here allows it
		// to be in the cache key without dark magic.
		if (self.filters.tbl_offset) {
			url += '&tbl_offset=' + self.filters.tbl_offset;
			delete self.filters.tbl_offset;
		}

		// Always send an XID
		self.filters.XID = EE.XID;

		// fire request start event (show progress indicator)
		$.ajax(url, {
			type: 'post',
			data: self.filters,
			success: function(data) {

				// parse data
				data.html_rows = $.tmpl(self.template_id, data.rows);
				data.pagination = self.pagination.parse(data.pagination);

				// add to cache
				self.cache.set(cache_id, data, data.rows.length);
				success(data);
			},
			dataType: 'json'
		});
	},

	/**
	 * Weed out the stuff we don't want in there, like XIDs,
	 * session ids, and blank values

	 * Also take this opportunity to create a stable cache key, as
	 * some browsers sort objects and some do not =( . To get consistency
	 * for those that don't sort, we push keys and values into an array,
	 * sort the array, and concat to get a string. -pk
	*/
	_prep_for_cache: function() {
		this.filters.tbl_sort = this.sort.get();

		var key, regex = /^(XID|S|D|C|M)$/,
			cache_key_relevant = [];

		for (key in this.filters) {
			if (this.filters[key] == '' || regex.exec(key) !== null) {
				delete this.filters[key];
			} else {
				cache_key_relevant.push(key, this.filters[key]);
			}
		}

		cache_key_relevant.sort();

		return cache_key_relevant.join(''); // debug $.param(this.filters);
	},

	/**
	 * Helper method to set the filter object
	 * from form elements.
	 */
	_set_filter: function(obj) {
		var els = obj.serializeArray(),
			self = this;

		$.each(els, function() {
			self.filters[this.name] = this.value;
		});
	},

	/**
	 * Event data helper
	 *
	 * Should reflect the state most hooks might care about
	 */
	_ui: function(add) {
		add = add || {};

		return $.extend({
			sort: this.sort.get(),// sort order [[column, asc/desc], [column2, asc/desc]]
			filters: this.filters // all applied filters
		}, add);
	}

});


// --------------------------------------------------------------------------


/**
 * Implements a LRU (least-recently-used) cache.
 */
function Cache(limit) {
	this.size = 0;
	this.limit = limit;
	this.cache = [];	 // [[page, data], [page2, data2]]
	this.cache_map = []; // [page, page, page] for faster access
}

Cache.prototype = {

	/*
	 * Get the cache limit
	 */
	limit: function() {
		return this.limit;
	},

	/*
	 * Get current cache size
	 */
	size: function() {
		return this.cache.length;
	},

	/**
	 * Add a cache item
	 *
	 * @param string	unique identifier
	 * @param mixed		data to cache
	 * @param int		penalty against cache limit [default = 1]
	 *
	 * We cache per page, but since our page length is variable, we want
	 * to control cache size per row. Cache_weight exists so that this
	 * plugin remains decoupled.
	 */
	set: function(id, data, cache_weight) {
		var penalty = cache_weight || 1;

		// evict data until this item fits
		while (this.size + penalty > this.limit) {
			var evicted = this.cache.shift();
			this.cache_map.shift();

			this.size -= evicted[2];
		}

		this.cache.push( [id, data, penalty] );
		this.cache_map.push(id);
		this.size += penalty;

		return this;
	},

	/**
	 * Get a cached item
	 *
	 * If the cache key exists, it is moved to the top
	 * of a stack to avoid eviction (LRU behavior).
	 *
	 * @param	string	cache id
	 * @return	mixed	cached item or null
	 */
	get: function(id) {
		var el, loc = this._find(id);

		if (loc > -1) {
			// detach and push on top of the queue (newest element)
			el = this.cache.splice(loc, 1)[0];
			this.cache.push(el);

			// fix up our map
			this.cache_map.splice(loc, 1);
			this.cache_map.push(el[0]);

			return el[1];
		}

		return null;
	},

	/**
	 * Delete a cached item
	 */
	'delete': function(id) {
		var el, loc = this._find(id);

		if (loc > -1) {
			el = this.cache.splice(loc, 1);
			this.cache_map.splice(loc, 1);
			this.size -= el[2];
		}

		return this;
	},

	/**
	 * Clear cache
	 */
	clear: function() {
		this.size = 0;
		this.cache = [];
		this.cache_map = [];

		return this;
	},

	/**
	 * Find item in cache
	 *
	 * Helper method as IE does not support indexOf
	 * on arrays. This is also the reason why cache_map
	 * exists: we can search it with a native function
	 * and it's faster to iterate if we fall back.
	 */
	_find: function(id) {
		// oh hello there IE
		if ( ! Array.prototype.indexOf) {
			var tmp = this.cache_map,
				len = tmp.length,
				i = 0;

			for (; i < len; i++) {
				if (tmp[i] == id) {
					return i;
				}
			}
			return -1;
		}

		// native functions!
		return this.cache_map.indexOf(id);
	}
};



/**
 * Table pagination class
 */
function Pagination(options, plugin) {
	var self = this;

	this.els = $('p.' + options.uniqid);
	this.template_id = options.uniqid + '_pag_template';

	// compile the template
	$.template(this.template_id, options.pagination);


	// _request will grab the new page, and then call update
	this.els.delegate('a', 'click', function() {
		var filters = self._extract_qs(this.href, plugin.options.base_url);

		plugin.add_filter(filters);
		return false;
	});
}

Pagination.prototype = {

	/**
	 * Parse the pagination data
	 *
	 * Only parsed once and then stuck into the
	 * page cache along with its data
	 */
	parse: function(data) {
		if ( ! data) {
			return '';
		}

		return $.tmpl(this.template_id, data).html();
	},

	/**
	 * Update the pagination html
	 *
	 * @param mixed results from parse [cached]
	 */
	update: function(data) {
		if ( ! data) {
			this.els.html('');
			return;
		}

		this.els.html(data).show();
	},

	/**
	 * Get the pagination html
	 *
	 * Used to fill the initial cache
	 */
	html: function() {
		return this.els.html();
	},

	// Private methods //

	/**
	 * Extract Query String from link
	 *
	 * Needed to allow pagination on "saved" searches,
	 * where the keywords might be in the url and we need
	 * to manually apply them to the next page.
	 */
	_qs_splitter: new RegExp('([^&=]+)=?([^&]*)', 'g'),
	_extract_qs: function(url, base) {
		url = url.replace(base, '');

		var seg,
			idx = url.indexOf('?'),
			res = {};

		// only work through the qs
		if (idx > 0) {
			url = url.slice(idx + 1);
		}

		while ( (seg = this._qs_splitter.exec(url)) ) {
			res[ decodeURIComponent(seg[1]) ] = decodeURIComponent(seg[2]);
		}

		return res;
	}
};


/**
 * Table sorting class
 */
function Sort(options, plugin) {
	var self = this;

	this.sort = [];
	this.plugin = plugin;
	this.headers = plugin.element.find('th');
	this.css = {
		'asc': options.cssAsc,
		'desc': options.cssDesc
	};

	// helpers
	this.header_map = {};
	this._initial_sort = options.sort;


	// @todo pass css and sort prefs configs (initial + allowed columns)
	// @todo make our own based on the php example sort?
	if ( ! options.pagination) {
		$(plugin.element).tablesorter();
		return;
	}

	// cache all headers and check if we want
	// them to be sortable

	this.headers.each(function() {
		var el = $(this),
			short_name = el.data('table_column');
		self.header_map[ short_name ] = el;

		el.data('sortable', options.columns[short_name].sort);
	});

	// setup events

	plugin.element.find('thead')
		.delegate('th', 'selectstart', function() { return false; }) // don't select with shift
		.delegate('th', 'click', function(e) {
			var el = $(this);

			// allow things like checkboxes inside table headers
			if (el.has('input').length) {
				return true;
			}

			// if holding shift key: add
			if ( ! el.data('sortable')) {
				return false;
			}

			var fn = e.shiftKey ? 'add' : 'set';
			self[fn](
				el.data('table_column'),
				el.hasClass(options.cssAsc) ? 'desc' : 'asc'
			);

			return false;
	});

	// setup initial sort without making a request
	// @todo, this could be better
	var l = this._initial_sort.length;

	while (l--) {

		var this_header_map = this.header_map[ this._initial_sort[l][0] ];

		if (this_header_map !== undefined)
		{
			this.sort.push(this._initial_sort[l]);
			this_header_map
				.toggleClass(this.css.asc, (this._initial_sort[l][1] === 'asc'))
				.toggleClass(this.css.desc, (this._initial_sort[l][1] === 'desc'));
		};
	}
}

Sort.prototype = {

	/**
	 * Get current sort
	 *
	 * @param	string	column name for sort to return [optional]
	 * @return	mixed	full sort array | sort direction of column | null
	 */
	get: function(column) {
		if (column) {
			var l = this.sort.length;

			while (l--) {
				if (this.sort[l][0] == column) {
					return this.sort[l][1];
				}
			}

			return null;
		}

		return this.sort;
	},

	/**
	 * Add sort to column
	 *
	 * @param	string	column name (or full sort array, see set)
	 * @param	string	sort direction [asc|desc]
	 */
	add: function(column, dir) {
		var sort = column, l;

		if (dir) {
			sort = [[column, dir]];
		}

		// @todo fire addSort events

		l = sort.length;
		while (l--) {
			this.sort.push(sort[l]);
			this.header_map[ sort[l][0] ]
				.toggleClass(this.css.asc, (sort[l][1] === 'asc'))
				.toggleClass(this.css.desc, (sort[l][1] === 'desc'));

			// @todo event
			//this._trigger('sort', null, this._ui(/* @todo args sort[l] */));
		}

		this.plugin.refresh();
		return this;
	},

	/**
	 * Set sort
	 *
	 * @param	mixed	sort array ([[field, dir], [field2, dir]])
	 */
	set: function(column, dir) {

		// clear and add
		this.clear();
		this.add(column, dir);

		return this;
	},

	/**
	 * Reset sort to initial conditions
	 */
	reset: function() {
		this.clear();
		this.set(this._initial_sort);

		this.plugin.refresh();
		return this;
	},

	/**
	 * Clear sort entirely, does not reset
	 */
	clear: function() {
		var l = this.sort.length;

		while (l--) {
			this.header_map[ this.sort[l][0] ].removeClass(
				this.css.asc + ' ' + this.css.desc
			);
			// @todo event
			// this._trigger('nosort', null, this._ui(/* @todo args this.sort[l] */));
		}

		this.sort = [];
		return this;
	}
}

})(jQuery);
