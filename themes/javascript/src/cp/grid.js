(function($) {

	function Grid(field, settings)
	{
		this.root = $(field);
		this.blankRow = this.root.find('tr.blank_row');
		this.emptyField = this.root.find('tr.empty_field');
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
			this._toggleDeleteButtons();

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
			// Figure out how many rows we need to add, plus 2 to account for
			// the blank template row and empty field row
			var rowsCount = this._getNumberOfRows(),
				neededRows = this.settings.grid_min_rows - rowsCount;

			// Show empty field message if field is empty and no rows are needed
			if (rowsCount == 0 && neededRows == 0)
			{
				this.emptyField.show();
				this.find
			}

			while (neededRows > 0)
			{
				this._addRow();

				neededRows--;
			}
		},

		/**
		 * Looks at current row count, and if it's above the minimum rows setting,
		 * shows the delete buttons; otherwise, hides the delete buttons
		 */
		_toggleDeleteButtons: function()
		{
			var rowCount = this._getNumberOfRows();
			var deleteButtons = this.root.find('.grid_button_delete');

			if (rowCount <= this.settings.grid_min_rows)
			{
				deleteButtons.hide();
			}
			else
			{
				deleteButtons.show();
			}
		},

		/**
		 * Returns current number of data rows in the Grid field
		 */
		_getNumberOfRows: function()
		{
			return this.rowContainer.find('tr').not(this.blankRow.add(this.emptyField)).size()
		},

		/**
		 * Binds click listener to Add button to insert a new row at the bottom
		 * of the field
		 */
		_bindAddButton: function()
		{
			var that = this;

			this.root.find('.grid_button_add, .grid_link_add').on('click', function(event)
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

			// Append the row to the end of the row container
			this.rowContainer.append(el);

			// Make sure empty field message is hidden
			this.emptyField.hide();

			// Hide/show delete buttons depending on minimum row setting
			// TODO: Handle max rows
			this._toggleDeleteButtons();

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