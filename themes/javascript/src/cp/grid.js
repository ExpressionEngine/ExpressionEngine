(function($) {

	/**
	 * Grid Namespace
	 */
	var Grid = window.Grid = {

		// Event handlers stored here, direct access outside only from
		// Grid.Publish class
		_eventHandlers: [],

		/**
		 * Binds an event to a fieldtype
		 *
		 * Available events are:
		 * 'display' - When a row is displayed
		 * 'remove' - When a row is deleted
		 * 'beforeSort' - Before sort starts
		 * 'afterSort' - After sort ends
		 * 
		 * @param	{string}	fieldtypeName	Class name of fieldtype so the
		 *				correct cell object can be passed to the handler
		 * @param	{string}	action			Name of action
		 * @param	{func}		func			Callback function for event
		 */
		bind: function(fieldtypeName, action, func)
		{
			if (this._eventHandlers[action] == undefined)
			{
				this._eventHandlers[action] = [];
			}

			// Each fieldtype gets one method per handler
			this._eventHandlers[action][fieldtypeName] = func;
		}
	}

	/**
	 * Grid Publish class
	 * 
	 * @param	{string}	field		Field ID of table to instantiate as a Grid
	 * @param	{string}	settings	JSON string of field settings
	 */
	Grid.Publish = function(field, settings)
	{
		this.root = $(field);
		this.blankRow = this.root.find('tr.blank_row');
		this.emptyField = this.root.find('tr.empty_field');
		this.rowContainer = this.root.find('.grid_row_container');
		this.settings = settings;
		this.init();

		this.eventHandlers = [];
	}

	Grid.Publish.prototype = {

		init: function()
		{
			this._addMinimumRows();
			this._bindSortable();
			this._bindAddButton();
			this._bindDeleteButton();
			this._toggleRowManipulationButtons();

			var that = this;
			
			window.setTimeout(function()
			{
				that._getRows().each(function()
				{
					that._fireEvent('display', $(this));
				});
			}, 500);

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
				sort: EE.sortable_sort_helper,	// Custom sort handler
				helper: function(e, tr)			// Fix issue where cell widths collapse on drag
				{
					var $originals = tr.children();
					var $helper = tr.clone();

					$helper.children().each(function(index)
					{
						// Set helper cell sizes to match the original sizes
						$(this).width($originals.eq(index).width())
					});

					return $helper;
				},
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
			var rowsCount = this._getRows().size(),
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
			var rowCount = this._getRows().size();

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
		_getRows: function()
		{
			return this.rowContainer
				.find('tr.grid_row')
				.not(this.blankRow.add(this.emptyField));
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

			// TODO: Put row ID in data attribute, come up with a way
			// to tell developers this is a new row

			// Append the row to the end of the row container
			this.rowContainer.append(el);

			// Make sure empty field message is hidden
			this.emptyField.hide();

			// Hide/show delete buttons depending on minimum row setting
			this._toggleRowManipulationButtons();

			// Fire 'display' event for the new row
			this._fireEvent('display', el);
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

				row = $(this).parents('tr.grid_row');

				// Fire 'remove' event for this row
				that._fireEvent('remove', row);

				// Remove the row
				row.remove();

				that._toggleRowManipulationButtons();

				// Show our empty field message if we have no rows left
				if (that._getRows().size() == 0)
				{
					that.emptyField.show();
				}
			});
		},

		/**
		 * Called after main initialization to fire the 'display' event
		 * on pre-exising rows
		 */
		_fieldDisplay: function()
		{
			var that = this;

			this._getRows().each(function()
			{
				that._fireEvent('display', $(this));
			});
		},

		/**
		 * Fires event to fieldtype callbacks
		 * 
		 * @param	{string}		action	Action name
		 * @param	{jQuery object}	row		jQuery object of affected row
		 */
		_fireEvent: function(action, row)
		{
			// If no events regsitered, don't bother
			if (Grid._eventHandlers[action] === undefined)
			{
				return;
			}
			
			// For each fieldtype binded to this action
			for (var fieldtype in Grid._eventHandlers[action])
			{
				// Find the sepecic cell(s) for this fieldtype and send each
				// to the fieldtype's event hander
				row.find('td[data-fieldtype="'+fieldtype+'"]').each(function()
				{
					Grid._eventHandlers[action][fieldtype]($(this));
				});
			}
		}
	};

	/**
	 * Public method to instantiate
	 */
	EE.grid = function(field, settings)
	{
		return new Grid.Publish(field, settings);
	};

})(jQuery);