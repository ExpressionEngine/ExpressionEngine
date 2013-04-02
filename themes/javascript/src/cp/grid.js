(function($) {

	function Grid(field, settings)
	{
		this.root = $(field);
		this.blankRow = this.root.find('tr.blank_row');
		this.rowContainer = this.root.find('.grid_row_container');
		this.settings = settings;
		this.init();
	}

	Grid.prototype = {

		init: function()
		{
			this._addMinimumRows();
			this._bindSortable();
			this._bindAddButton();

			// Disable input elements in our blank template container so they
			// don't get submitted on form submission
			this.blankRow.find(':input').attr('disabled', 'disabled');
		},

		/**
		 * Allows columns to be reordered
		 */
		_bindSortable: function()
		{
			this.rowContainer.sortable({
				axis: 'y',						// Only allow vertical dragging
				containment: 'parent',			// Contain to parent
				handle: 'td.grid_handle',		// Set drag handle to the top box
				items: 'tr',					// Only allow these to be sortable
				sort: EE.sortable_sort_helper	// Custom sort handler
			});
		},

		_addMinimumRows: function()
		{
			// Figure out how many rows we need to add, plus 1 to account for
			// the blank template row we already have
			var neededRows = this.settings.grid_min_rows - this.rowContainer.find('tr').size() + 1;

			while (neededRows > 0)
			{
				this._addRow();

				neededRows--;
			}
		},

		/**
		 * Binds click listener to Add button to insert a new row at the bottom
		 * of the field
		 */
		_bindAddButton: function()
		{
			var that = this;

			this.root.siblings('.grid_button_add').on('click', function(event)
			{
				event.preventDefault();

				that._addRow();
			});
		},

		/**
		 * Inserts new row at the bottom of our field
		 */
		_addRow: function()
		{
			// Clone our blank row
			el = this.blankRow.clone();

			// Remove any classes, such as 'blank_row'
			el.removeClass();

			// Increment namespacing on inputs
			el.html(
				el.html().replace(
					RegExp('new_row_[0-9]{1,}', 'g'),
					'new_row_' + this.rowContainer.find('tr').size()
				)
			);

			// Enable inputs
			el.find(':input').removeAttr('disabled');

			// Finally, append the row to the end of the row container
			this.rowContainer.append(el);

			// TODO: fire JS event so fieldtypes can bind listeners and things
		}
	};

	/**
	 * Public method to instantiate
	 */
	EE.grid = function(field, settings)
	{
		return new Grid(field, settings);
	};

})(jQuery);