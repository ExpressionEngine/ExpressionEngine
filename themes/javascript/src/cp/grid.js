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
			this._bindDeleteButton();
			this._toggleRowManipulationButtons();

			// Disable input elements in our blank template container so they
			// don't get submitted on form submission
			this.blankRow.find(':input').attr('disabled', 'disabled');
		},

		/**
		 * Allows rows to be reordered
		 */
		_bindSortable: function()
		{
			this.rowContainer.sortable({
				axis: 'y',						// Only allow vertical dragging
				containment: 'parent',			// Contain to parent
				handle: 'td.grid_handle',		// Set drag handle
				items: 'tr.grid_row',			// Only allow these to be sortable
				sort: EE.sortable_sort_helper	// Custom sort handler
			});
		},

		/**
		 * Adds rows to a Grid field based on the fields minimum rows setting
		 * and how many rows already exist
		 */
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
			}

			// Add the needed rows
			while (neededRows > 0)
			{
				this._addRow();

				neededRows--;
			}
		},

		/**
		 * Toggles the visibility of the Add button and Delete buttons for rows
		 * based on the number of rows present and the max and min rows settings
		 */
		_toggleRowManipulationButtons: function()
		{
			var rowCount = this._getNumberOfRows();

			if (this.settings.grid_max_rows !== '')
			{
				var addButton = this.root.find('.grid_button_add');

				// Show add button if row count is below the max rows setting
				addButton.toggle(rowCount < this.settings.grid_max_rows);
			}

			if (this.settings.grid_min_rows !== '')
			{
				var deleteButtons = this.root.find('.grid_button_delete');

				// Show delete buttons if the row count is above the min rows setting
				deleteButtons.toggle(rowCount > this.settings.grid_min_rows);
			}
		},

		/**
		 * Returns current number of data rows in the Grid field
		 *
		 * @return	{int}	Number of rows
		 */
		_getNumberOfRows: function()
		{
			return this.rowContainer
				.find('tr.grid_row')
				.not(this.blankRow.add(this.emptyField))
				.size()
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

			el.removeClass('blank_row');

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
			this._toggleRowManipulationButtons();

			// TODO: fire JS event so fieldtypes can bind listeners and things
		},

		/**
		 * Binds click listener to Delete button in row column to delete the row
		 */
		_bindDeleteButton: function()
		{
			var that = this;

			this.root.on('click', '.grid_button_delete', function(event)
			{
				event.preventDefault();

				// Remove the row
				$(this).parents('tr.grid_row').remove();

				that._toggleRowManipulationButtons();

				// Show our empty field message if we have no rows left
				if (that._getNumberOfRows() == 0)
				{
					that.emptyField.show();
				}
			});
		},
	};

	/**
	 * Public method to instantiate
	 */
	EE.grid = function(field, settings)
	{
		return new Grid(field, settings);
	};

})(jQuery);