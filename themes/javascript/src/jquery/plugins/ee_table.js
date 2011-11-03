(function($) {



$.widget('ee.table', {
		
	_listening: [],			// form elements the filter is listening to
	
	options: {
		uniqid: null,
		
		form: null,			// element(s)
		pagination: null,	// element(s)
		
		template: null,		// table template
		pag_template: null,	// pagination template
		
		columns: [],		// column names to match_data / search, filter on the spot
		
		cache_limit: 5,
		
		page: 1,
		filters: {},
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
		self.pagination = new Pagination(options, self);
		
		// create unique template name and compile
		self.template_id = options.uniqid + '_row_template';
		$.template(self.template_id, options.template);
		
		// @todo @pk ideas -------------
		
		// bind to table headers
		// bind events
		self._trigger('create', null, self._ui(/* @todo args */));
		self._trigger('update', null, self._ui(/* @todo args */));
	},
	
	clear_filters: function() {
		// @todo reset form content?
		
		self.filters = {};
		self._listening.each(function() {
			$(this).unbind('interact.ee_table');
		});
	},

	/* Add a filter
	 *
	 * Can be a form or a regular object
	 */
	add_filter: function(obj) {
		var self = this;
			url = EE.BASE + '&C=content_edit'; // window.location.href
		
		// add to filters and update right away
		// @todo do not hardcode url!
		if ($.isPlainObject(obj)) {
			self.filters = $.extend(self.filters, obj);
			self._request(url);
			return true;
		}

		// @todo timeout on some inputs (textareas)
		
		
		var form = obj.closest('form');
		
		if (form) {
			url = form.attr('action');
		}
		
		// @todo bind to submit only if it's a form?
		$(obj).bind('interact.ee_table', function() {
			self._set_filter(obj);			
			self._request(form.action);
			return false;
		});
		
		self._listening.push(obj);
	},
	
	add_sort: function(settings) {
		
		;
		
	},

	_request: function(url, callback) {
		var self = this,
			body = self.element.find('tbody'),
			data, success;
		
		success = function(res) {
			self._trigger('update', null, self._ui(/* @todo args */));
			
			// parse table rows
			// @todo only remove those that are not in the result set
			// don't need to reparse any others
			body.empty();
			
			$.each(res.rows, function() {
				var parsed = $.tmpl(self.template_id, this);
				body.append(parsed);
			});
			
			self.pagination.update(res.pagination);
		};
		
		// @todo normalize url with filter data
		
		data = self.cache.get(url);
		if (data !== null) {
			return success(data);
		}
		
		// Always dd the xid
		self.filters['XID'] = EE.XID;
		
		// fire request start event (show progress indicator)
		$.ajax(url, {
			type: 'post',
			data: self.filters,
			success: function(data) {
				// fire request done event (hide progress indicator)
				// @todo caching
				// self.cache.set(url, data);
				success(data);
			},
			dataType: 'json'
		});
		
		
		// @todo if they want to remap? not sure how to handle columns from php end
		// @todo more like not sure where to draw the line, actually
		// columns = self._trigger('beforerender', null, self._ui(/* @todo args */));
	},
	
	_ui: function() {
		return {
			page: 0, // @todo current page
			sort: [], // @todo sort order [[column, asc/desc], [column2, asc/desc]]
			filter: [], // all applied filters
		};
	},
	
	_set_filter: function(obj) {
		var els = obj.serializeArray(),
			self = this;
		
		$.each(els, function() {
			self.filters[this.name] = this.value;
		});
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
		if (this.cache.length >= this.limit) {
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
		
		// @todo finish caching
		return null;

		return this.cache[ this.cache_map[page] ][1];
	},

	clear: function() {
		this.cache = [];
		this.cache_map = {};
	}
};



// --------------------------------------------------------------------------

/**
 * Table pagination class
 */
function Pagination(options, plugin) {	
	var els = $('p.' + options.uniqid),
		template_id = options.uniqid + '_pag_template';
	
	// compile the template
	$.template(template_id, options.pagination);
	
	// _request will grab the new page, and then call update
	els.delegate('a', 'click', function() {
		plugin._request(this.href);
		return false;
	});
	
	
	// call with new pagination array to rebuild
	// @todo should this be listening on an update event on the plugin?
	// answer is no, unless it also does not receive the plugin to begin with (/is a standalone thing)
	this.update = function(data) {
		if ( ! data) {
			els.html('');
			return;
		}
		
		var res = $.tmpl(template_id, data);
		els.html(res.html());
	};
}


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