(function($) {






$.widget('ee.table', {
	options: {
		uniqid: null,
		
		form: null,			// element(s)
		pagination: null,	// element(s)
		
		template: null,		// table template
		pag_template: null,	// pagination template
		
		columns: [],		// column names to match_data / search, filter on the spot
		
		cache_limit: 5,
		
		page: 1,
		filters: [],
		sorting: [],		// [[column, desc], [column2, asc]]
		
		// events as callbacks
		create: null,
		update: null
	},
	
	_create: function() {
		this.forms = $();
				
		var self = this,
			options = self.options
		
		self.page = options.page;
		self.filters = options.filters;
		self.sorting = options.sorting;
		
		self.cache = new Cache(options.cache_limit);
		self.pagination = self._setup_pagination(options.pagination);
		
		// create unique template name and compile
		self.template = options.uniqid + '_row_template';
		$.template(self.template, options.template);		
		
		if (options.pagination !== null) {
			self._setup_pagination();
		}
		
		// @todo @pk ideas -------------
		
		// bind to table headers
		// bind events
		self._trigger('create', null, self._ui(/* @todo args */));
		self._trigger('update', null, self._ui(/* @todo args */));
		
		/*
		this.element // current table
		
		// @todo map to form?
		this.columns = this.element.find('th');
		
		if (options.form) {
			// @todo bind to all fields
			$(options.form).bind('submit', self._filter);
			
			// @todo pseudo code
			$(options.form).find('input').bind('change', self._filter);
			var fields = $(options.form).find(/* @todo * /);
			
		}
		*/
		// @todo timeout on some inputs (textareas)
	},
	
	clear_filters: function() {
		// if form connected, reset content
	},

	add_filter: function(settings) {
		
		settings.source
		
		// object can be a form or a regular object
		// 
		
				
		var self = this;
		$(form).submit(function() {
			self.filters = $(form).serialize();
			self._request(this.action);
			return false;
		})
	},

	_request: function(url, callback) {
		var self = this,
			body = self.element.find('tbody'),
			data, success;
		
		success = function(res) {
			self._trigger('update', null, self._ui(/* @todo args */));
			
			// parse table rows
			$.each(res.rows, function() {
				var parsed = $.tmpl(self.template, this);
				body.append(parsed);
			});
			
			var current = self.options.pagination.html();
			
			if (current && res.pagination_html == '')
			{
				self.options.pagination.fadeOut('fast');
			}
			else
			{
				self.options.pagination.html($(res.pagination_html).html());
			}
			
			
			if (res.pagination_html && self.options.pagination.is(':hidden')) {
				self.options.pagination.fadeIn('fast');
			}
		};
		
		// @todo normalize url with filter data
		
		data = self.cache.get(url);
		if (data !== null) {
			return success(data);
		}
		
		// fire request start event (show progress indicator)
		
		$.ajax(url, {
			type: (self.filters.length) ? 'post' : 'get',
			data: self.filters,
			success: function(data) {
				// fire request done event (hide progress indicator)
				self.cache.set(url, data);
				success(data);
			},
			dataType: 'json'
		});
		
		
		// @todo if they want to remap? not sure how to handle columns from php end
		// @todo more like not sure where to draw the line, actually
		// columns = self._trigger('beforerender', null, self._ui(/* @todo args */));
	},
	
	_setup_pagination: function(config) {
		if ( ! config) {
			return false;
		}
		
		if ('display_pages' in config && ! config.display_pages) {
			return false;
		}
		
		var self = this,
			els = $('.' + self.uniqid);
		
		if ( ! els.length) {
			return false;
		}
		
		return new Pagination(page_els, config, self);
	},
	
	_ui: function() {
		return {
			page: 0, // @todo current page
			sort: [], // @todo sort order [[column, asc/desc], [column2, asc/desc]]
			filter: [], // all applied filters
		};
	}
	
});


// --------------------------------------------------------------------------


/**
 * Table caching class
 */
function Cache(limit) {
	this.limit = limit;
	this.cache = [];	 // [[page, data], [page2, data2]]
	this.cache_map = {}; // stores page_# -> index (this.cache) for faster access
}

Cache.prototype = {
	set: function(page, data) {
		var el, len;

		// updating existing?
		if (page in this.cache_map) {
			// detach
			el = this.cache.splice(this.cache_map[page], 1);

			// update
			el[1] = data;

			// push on top of the queue (newest element)
			len = this.cache.push(el);
			this.cache_map[page] = len - 1;

			return this;
		}

		// evict
		if (this.cache.length >= limit) {
			el = this.cache.shift();
			delete this.cache_map[ el[0] ];
			delete el;
		}

		len = this.cache.push( [page, data] );
		this.cache_map[page] = len - 1;

		return this;
	},

	get: function(page) {
		if ( ! page in this.cache_map) {
			return null;
		}

		return this.cache[ this.cache_map[page] ][1];
	},

	clear: function() {
		this.cache = [];
		this.cache_map = {};
	}
}

Cache.prototype = {
	set: function(page, data) {
		var el, len;

		// updating existing?
		if (page in this.cache_map) {
			// detach
			el = this.cache.splice(this.cache_map[page], 1);

			// update
			el[1] = data;

			// push on top of the queue (newest element)
			len = this.cache.push(el);
			this.cache_map[page] = len - 1;

			return this;
		}

		// evict
		if (this.cache.length >= limit) {
			el = this.cache.shift();
			delete this.cache_map[ el[0] ];
			delete el;
		}

		len = this.cache.push( [page, data] );
		this.cache_map[page] = len - 1;

		return this;
	},

	get: function(page) {
		if ( ! page in this.cache_map) {
			return null;
		}

		return this.cache[ this.cache_map[page] ][1];
	},

	clear: function() {
		this.cache = [];
		this.cache_map = {};
	}
}


// --------------------------------------------------------------------------


/**
 * Table pagination class
 */
function Pagination(els, config, plugin) {
	this.total_rows			= ''; // Total number of items (database results)
	this.per_page			= 10; // Max number of items you want shown per page
	this.num_links			=  2; // Number of "digit" links to show before/after the currently viewed page
	this.cur_page			=  0; // The current page being viewed
	this.first_link			= '&lsaquo; First';
	this.next_link			= '&gt;';
	this.prev_link			= '&lt;';
	this.last_link			= 'Last &rsaquo;';
	this.uri_segment		= 3;
	this.full_tag_open		= '';
	this.full_tag_close		= '';
	this.first_tag_open		= '';
	this.first_tag_close	= '&nbsp;';
	this.last_tag_open		= '&nbsp;';
	this.last_tag_close		= '';
	this.first_url			= ''; // Alternative URL for the First Page.
	this.cur_tag_open		= '&nbsp;<strong>';
	this.cur_tag_close		= '</strong>';
	this.next_tag_open		= '&nbsp;';
	this.next_tag_close		= '&nbsp;';
	this.prev_tag_open		= '&nbsp;';
	this.prev_tag_close		= '';
	this.num_tag_open		= '&nbsp;';
	this.num_tag_close		= '';
	this.page_query_string	= false;
	this.query_string_segment = 'per_page';
	this.display_pages		= true;
	this.anchor_class		= '';
	
	els.delegate('a', 'click', function() {
		plugin._request(this.href);
		return false;
	});
}

function create_links() {

	this.total_rows = 2014;
	this.current_offset = 50;
	this.per_page = 50;

	var num_links = 5, /* @todo get from config */
		total_pages = Math.ceil(this.total_rows / this.per_page),
		current_page = Math.floor((this.offset/this.per_page) + 1),

		start = current_page - num_links - 2,
		end = this.current_page + num_links;

	// if total_pages is 1, do nothing

	// Adjust to fit our limits

	if (current_page > this.total_rows) {
		current_page = (total_pages - 1) * this.per_page;
	}

	if (start < 0) {
		start = 0;
	}

	if (end > total_pages) {
		end = total_pages;
	}

	var base_url = this.base_url + '&amp' + this.query_string_segment + '=',
		that = this,
		create_url, tmp_data;

	// create_url lookup table
	tmp_data = {
		'first':	{'tag_open': this.first_tag_open, 'tag_close': first_tag_close, 'text': this.first_link},
		'prev':		{'tag_open': this.prev_tag_open, 'tag_close': prev_tag_close, 'text': this.prev_link},
		'current':	{'tag_open': this.cur_tag_open, 'tag_close': cur_tag_close},
		'others':	{'tag_open': this.num_tag_open, 'tag_close': num_tag_close},
		'next':		{'tag_open': this.next_tag_open, 'tag_close': next_tag_close, 'text': this.next_link},
		'last':		{'tag_open': this.last_tag_open, 'tag_close': last_tag_close, 'text': this.last_link}
	};

	// create_url link template
	$.template(
		'link_format',
		'${tag_open}<a ' +this.anchor_class+ 'href="${url}">${text}<a/>${tag_close}'
	);

	// quick utility function
	create_url = function(type, offset, text) {
		var data = tmp_data[type],
			url = that.first_url;

		if (type == 'current') {
			return data.tag_open + data.offset + data.tag_close;
		}

		if ( ! (offset == 0 && that.first_url)) {
			url = base_url + that.prefix + offset + that.suffix;
		}

		data['url'] = url;

		if (text) {
			data['text'] = text;
		}

		return $.tmpl('link_format', data);
	}


	var inner_html = '',
		i = this.offset - this.per_page;

	// first link
	if (this.first_link !== false && current_page > (num_links + 1)) {
		inner_html += create_url('first', 0);
	}

	// prev link
	if (this.prev_link !== false && current_page != 1) {
		inner_html += create_url('prev', i);
	}

	while (start <= end) {
		i = (start * this.per_page) - this.per_page;

		if (i < 0) {
			start++;
			continue;
		}

		if (current_page == start) {
			inner_html += create_url('current', start);
		} else {
			inner_html += create_url('others', i, start);
		}

		start++;
	}

	// next link
	if (this.next_link !== false && current_page < total_pages) {
		inner_html += create_url('next', (current_page * this.per_page) );
	}

	// last link
	if (this.last_link !== false && (current_page + num_links) < total_pages) {
		inner_html += create_url('last',  (num_pages * this.per_page) - this.per_page);
	}

	return inner_html;
}










})(jQuery);

$(function() {
	$('table').each(function() {
		var config;
		
		if ($(this).data('table_config')) {
			$(this).table(config);
		}
	});
});



$.fn.table.add_sorter('numeric', {
	
});



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


$.fn.ee_table.add_sorter('alpha', function(a, b) {

});

 */