(function($) {


		
		// @todo @pk ideas -------------
		
		// ensure copyright notice on all files (I always forget ...)
		
		// first page currently isn't cached until you return to it
		// bind to table headers for sort (another object like pag?)
		
		
 		// edit specific:
		
		// add base64 thing to "add" tab link to "save" the search
		// clear search link (redirect to base path)
		
		
		// /@todo @pk ideas -------------


$.widget('ee.table', {
	
	_listening: $(),		// form elements the filter is listening to
	
	options: {
		uniqid: null,		// uniqid of related elements
		
		pagination: null,	// element(s)
		
		template: null,		// table template
		pag_template: null,	// pagination template
		
		columns: [],		// column names to match_data / search, filter on the spot
		
		cache_limit: 600,	// number of items, not pages!
		
		filters: {},
		sorting: []			// [[column, desc], [column2, asc]]
	},
	
	_create: function() {
		this.forms = $();
		
		var self = this,
			options = self.options;
		
		// set defaults
		self.filters = options.filters;
		self.sorting = options.sorting;
		
		// setup dependencies
		self.cache = new Cache(options.cache_limit);
		self.pagination = new Pagination(options, self);
		
		// create unique template name and compile
		self.template_id = options.uniqid + '_row_template';
		$.template(self.template_id, options.template);
		
		// bind events
		self._trigger('create', null, self._ui(/* @todo args */));
	},
	
	clear_cache: function() {
		this.cache.clear();
	},
	
	clear_filters: function() {
		// @todo reset form content?
		
		self.filters = {};
		self._listening.each(function() {
			$(this).unbind('interact.ee_table');
		});
	},

	/**
	 * Add a filter
	 *
	 * Can be a form or a regular object
	 */
	add_filter: function(obj) {
		var self = this,
			url = EE.BASE + '&C=content_edit'; // window.location.href
		
		// add to filters and update right away
		// @todo do not hardcode url!
		if ($.isPlainObject(obj)) {
			self._set_filter(self._listening);
			self.filters = $.extend(self.filters, obj);
			self._request(url);
			return true;
		}
		
		var form = obj.closest('form'),
			evts = 'interact.ee_table',
			_timeout;
		
		if (form) {
			url = form.attr('action');
			
			// bind to submit only if it's a form
			if (obj.is(form)) {
				evts += ' submit.ee_table';
			}
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
	},
	
	add_sort: function(sort) {
		
		;
		
	},

	_request: function(url, callback) {
		var self = this,
			body = self.element.find('tbody'),
			data, success;
		
		self._trigger('load', null, self._ui(/* @todo args */));

		// A cache hit and an ajax result below are both
		// considered successes and will call this with
		// the correct data =)
		success = function(res) {
			self._trigger('update', null, self._ui(/* @todo args */));
			
			// @todo only remove those that are not in the result set?
			
			body.html(res.rows);
			self.pagination.update(res.pagination);			
		};
		
		
		// Weed out the stuff we don't want in there, like XIDs and
		// session ids.
		
		// Also take this opportunity to create a stable cache key, as
		// some browsers sort objects and some do not =( . To get consistency
		// for those that don't sort, we push keys and values into an array,
		// sort the array, and concat to get a string. -pk
		
		var key, regex = /^(XID|S|D|C|M)$/,
			cache_key_relevant = [];
		
		for (key in self.filters) {
			if (self.filters[key] == '' || regex.exec(key) !== null) {
				delete self.filters[key];
			} else {
				cache_key_relevant.push(key, self.filters[key]);
			}
		}

		cache_key_relevant.sort();

		var cache_id = cache_key_relevant.join(''); // debug $.param(self.filters);
		
		// Do we have this page cached?
		data = self.cache.get(cache_id);
		if (data !== null) {
			return success(data);
		}
		
		// The pagination library reads from get, so we need
		// to move tbl_offset. Doing it down here allows it
		// to be in the cache key without dark magic.
		if (self.filters['tbl_offset']) {
			url += '&tbl_offset=' + self.filters['tbl_offset'];
			delete self.filters['tbl_offset'];
		}
		
		// Always send an XID
		self.filters['XID'] = EE.XID;
		
		// fire request start event (show progress indicator)
		$.ajax(url, {
			type: 'post',
			data: self.filters,
			success: function(data) {
				
				// parse data
				data.rows = $.tmpl(self.template_id, data.rows);
				data.pagination = self.pagination.parse(data.pagination);

				// add to cache
				self.cache.set(cache_id, data, data.rows.length);
				success(data);
			},
			dataType: 'json'
		});
	},
	
	_set_filter: function(obj) {
		var els = obj.serializeArray(),
			self = this;
		
		$.each(els, function() {
			self.filters[this.name] = this.value;
		});
	},
	
	_ui: function() {
		return {
			sorting: [], // @todo sort order [[column, asc/desc], [column2, asc/desc]]
			filters: self.filters, // all applied filters
		};
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
	
	// Limit getter
	limit: function() {
		return this.limit;
	},
	
	// Size getter
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
			
			console.log('cache:evict:'+evicted[0]);
		}

		console.log('cache:set: '+id);

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
			
			// detach and ush on top of the queue (newest element)
			el = this.cache.splice(loc, 1)[0];
			this.cache.push(el);

			// fix up our map
			this.cache_map.splice(loc, 1);
			this.cache_map.push(el[0]);

			console.log('cache:get: '+id);

			return el[1];
		}
		
		console.log('cache:miss: '+id);			
		
		return null;
	},
	
	/**
	 * Delete a cached item
	 */
	delete: function(id) {
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
				if (tmp[k] == id) {
					return k;
				}
			}
			return -1;
		}
		
		// native functions!
		return this.cache_map.indexOf(id);
	}
};



// --------------------------------------------------------------------------

/**
 * Table pagination class
 */
function Pagination(options, plugin) {	
	var self = this;
	
	this.els = $('p.' + options.uniqid),
	this.template_id = options.uniqid + '_pag_template';
	
	// compile the template
	$.template(this.template_id, options.pagination);
	
	
	// _request will grab the new page, and then call update	
	this.els.delegate('a', 'click', function() {
		var filters = self._extract_qs(this.href);

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
		
		this.els.html(data);
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
	_extract_qs: function(url) {
		var seg, idx, res = {};
		
		// only qs
		idx = url.indexOf('?')
		if (idx > 0) {
			url = url.slice(idx + 1);
		}
		
		while (seg = this._qs_splitter.exec(url)) {
			res[ decodeURIComponent(seg[1]) ] = decodeURIComponent(seg[2]);
		}
		
		return res;
	}
};


// --------------------------------------------------------------------------

// Go go go! Init all affected tables on the page
$('table').each(function() {
	var config;
	
	if ($(this).data('table_config')) {
		config = $(this).data('table_config');
		$(this).table(config);
	}
});

})(jQuery);




/*

/**
 * Brainstorming:
 *
 * As a third party I want to add a column, and in JS I want to
 * a) apply filtering
 * b) apply custom sorting to match my php sort


// Sorting:

// these sorting callbacks can be passed to native Array.sort(),
$.fn.ee_table.add_sorter('numeric', function(a, b) {
	return a-b;
});

// if you need to prep the value:
$.fn.ee_table.add_sorter('numeric', {
	format: function(el) { },
	compare: function(a, b) { return a-b; }
});

$('table').ee_table('sort_column', 'column', {
	format: function(el) {},
	compare: sorter_name/func
});

if both column and sorter have "format", column format result is passed to sorter format
$('table').ee_table('sort_column', column, sorter_name/function);


// Filtering:

$('table').ee_table('bind_filter', column, form_element);
$('table').ee_table('bind_filter', column, form_element2); // when one changes, so should the other


$('table').ee_table('auto_bind_filter', form);
$('table').ee_table('bind_filter', form, {
	'column': 'serialized_key',
	'column2': 'serialized_key2'
});

current_sort = $('table').ee_table('sort');
$('table').ee_table('sort', [[column, asc], [column2, desc]);

current_filters = $('table').ee_table('filter');
$('table').ee_table('filter', {'key': 'value', 'key2': 'value2'});


$('table').ee_table('sort_column', column, asc);

 */