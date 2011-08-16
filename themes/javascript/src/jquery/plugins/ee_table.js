(function($) {
	
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






$.widget('ee.table', {
	options: {
		form: null,			// element(s)
		pagination: null,	// element(s)
		
		template: null,		// table template
		pag_template: null,	// pagination template
		
		columns: [],		// column names to match_data / search, filter on the spot
		
		cache_limit: 5,
		
		page: 1,
		filters: [],
		sorting: [],
		
		// events as callbacks
		create: null,
		update: null
	},
	
	_create: function() {
		this.forms = $();
		
		console.log(self.options);
		
		var self = this,
			options = self.options;
		
		self.page = options.page;
		self.filters = options.filters;
		self.sorting = options.sorting;
		
		self.cache = new Cache(options.cache_limit);
				
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
	
	update: function(data) {
		// data._page // @todo update pagination
		// self.template.render(data._rows); // @todo fix up syntax
		
		
		self._trigger('update', null, self._ui(/* @todo args */));
		
		
		/*
		for (column in data) {
			if (column in this.options.columns)
		}
		*/
	},
	
	set_page: function(page) {
		var data = this.cache.get(page),
			self = this;
		
		if ( ! data) {
			this._async(function() {
				self.page = page;
			});
		} else {
			self.page = page;
		}
		
		return this;
	},
	
	set_filters: function() {
		// self.filters = // data;
	},
	clear_filters: function() {
		// if form connected, reset content
	},
	
	add_filter: function() {
		
	},
	remove_filter: function() {
		
	},
	
	_filter: function() {
		// @todo pseudo code
		if (this.name in columns) {
			// live filter
		}
		
		this._async(); // @todo fix scope here and for called func: this == element
	},
	
	_async: function(callback) {
		
		// show progress indicator
		
		
		// @todo if they want to remap? not sure how to handle columns from php end
		// @todo more like not sure where to draw the line, actually
		columns = self._trigger('beforerender', null, self._ui(/* @todo args */));
	},
	
	_ui: function() {
		return {
			page: 0, // @todo current page
			sort: [], // @todo sort order [[column, asc/desc], [column2, asc/desc]]
			filter: [], // all applied filters
		};
	}
	
});


// usage
/*
$('table').table({
	url: 'foo/bar/baz',
	form: $('some_form'),
	columns: ['data_name', 'data_name', 'data_name']
	create: function() {
		// created!
	}
});

*/

// Idea
// Only works if a template is available.
/*
$.extend($.ee.table, {
	cache: function(cache_limit) {
		
	}
	
	async: function(url, form /* or form elements * /) {
		// extend to add async filtering/sorting
		// by default just do sorting, filtering, and pagination if provided
		// pushes off this rather complex logic. I like it.
	},
	
	paginate: function(url) {}
});
*/

})(jQuery);


$('table').each(function() {
	var el;
	
	if (this['data-table_config']) {
		el = $(this);
		el.table(el.data('table_config'));
	}
})