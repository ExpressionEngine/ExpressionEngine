(function($) {

$.widget('ee.table', {
	options: {
		form: null,			// element(s)
		pagination: null,	// element(s)
		
		template: null,		// table template
		pag_template: null,	// pagination template
		
		columns: [],		// column names to match_data / search, filter on the spot
		
		cache_limit: 5,
		
		// events as callbacks
		create: null,
		update: null
	},
	
	_create: function() {
		var self = this,
			options = self.options;
		
		self.cache = [];
		self.cache_map = {};	// stores page_# -> cache_idx for faster and easier evicting
		
		self.form_cache = {};	// figure out if form data changed on submit | @todo need it?
		
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
		data._page // @todo update pagination
		self.template.render(data._rows); // @todo fix up syntax
		
		
		self._trigger('update', null, self._ui(/* @todo args */));
		
		
		/*
		for (column in data) {
			if (column in this.options.columns)
		}
		*/
	},
	
	_filter: function() {
		// @todo pseudo code
		if (this.name in columns) {
			// live filter
		}
		
		this._async(); // @todo fix scope here and for called func: this == element
	},
	
	_async: function() {
		
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
$.extend($.ee.table, {
	async: function(url, form /* or form elements */) {
		// extend to add async filtering/sorting
		// by default just do sorting, filtering, and pagination if provided
		// pushes off this rather complex logic. I like it.
	},
	
	paginate: function(url) {}
});

/* PHP ????:
$this->table->async(function, [$paginate=TRUE/false]);
// if ajax_request: automatically call func and return at this point?
// if not: trigger template creation, trigger additional data, pagination template if desired (add class to pagination elements)
*/


})(jQuery);