(function($) {

	function GridSettings()
	{
		this.root = $('#grid_settings');
		this.colTemplateContainer = $('#grid_col_settings_elements');
		this.blankColumn = this.colTemplateContainer.find('.grid_col_settings');

		this.init();
	}

	GridSettings.prototype = {

		init: function()
		{
			this._bindResize();
			this._bindAddButton();
			this._bindCopyButton();
			this._bindDeleteButton();
			this._toggleDeleteButtons();

			// Disable input elements in our blank template container so they
			// don't get submitted on form submission
			this.colTemplateContainer
				.find('input, select')
				.attr('disabled', 'disabled');
		},

		_bindResize: function()
		{
			// I dunno, do something about the scrolling
		},

		/**
		 * Binds click listener to Add button to insert a new column at the end
		 * of the columns
		 */
		_bindAddButton: function()
		{
			var that = this;

			this.root.find('.grid_button_add').on('click', function(event)
			{
				event.preventDefault();

				that._insertColumn(that._buildNewColumn());
			});
		},

		/**
		 * Binds click listener to Copy button in each column to clone the column
		 * and insert it after the column being cloned
		 */
		_bindCopyButton: function()
		{
			var that = this;

			this.root.on('click', 'a.grid_col_copy', function(event)
			{
				event.preventDefault();

				var parentCol = $(this).parents('.grid_col_settings');
				
				that._insertColumn(
					// Build new column based on current column
					that._buildNewColumn(parentCol),
					// Insert AFTER current column
					parentCol
				);
			});
		},

		/**
		 * Binds click listener to Copy button in each column to clone the column
		 * and insert it after the column being cloned
		 */
		_bindDeleteButton: function()
		{
			var that = this;

			this.root.on('click', '.grid_col_settings_delete', function(event)
			{
				event.preventDefault();
				
				$(this).parents('.grid_col_settings').remove();

				that._toggleDeleteButtons();
			});
		},

		/**
		 * Looks at current column count, and if there are multiple columns,
		 * shows the delete buttons; otherwise, hides delete buttons if there is
		 * only one column
		 */
		_toggleDeleteButtons: function()
		{
			var colCount = this.root.find('.grid_col_settings').size();
			var deleteButtons = this.root.find('.grid_col_settings_delete');

			if (colCount < 2)
			{
				deleteButtons.hide();
			}
			else
			{
				deleteButtons.show();
			}
		},

		/**
		 * Inserts a new column after a specified element
		 * 
		 * @param	{jQuery Object}	column		Column to insert
		 * @param	{jQuery Object}	insertAfter	Element to insert the column
		 *				after; if left blank, defaults to last column
		 */
		_insertColumn: function(column, insertAfter)
		{
			// Default to inserting after the last column
			if (insertAfter == undefined)
			{
				insertAfter = $('#grid_settings .grid_col_settings:last');
			}

			column.insertAfter(insertAfter);

			this._toggleDeleteButtons();
		},

		/**
		 * Builts new column from scratch or based on an existing column
		 * 
		 * @param	{jQuery Object}	el	Column to base new column off of, when
		 *				copying an existing column for example; if left blank,
		 *				defaults to blank column
		 */
		_buildNewColumn: function(el)
		{
			if (el == undefined)
			{
				el = this.blankColumn.clone();
			}
			else
			{
				// Clone our example column
				el = el.clone();
			}

			var colCount = $('.grid_col_settings').size();

			// Need to make sure the new columns field names are unique
			el.html(
				el.html().replace(
					RegExp('(new_|col_id_)[0-9]{1,}', 'g'), 'new_' + colCount
				)
			);

			// Make sure inputs are enabled if creating blank column
			el.find('input, select').removeAttr('disabled');

			return el;
		},
	};

	/**
	 * Public method to instantiate
	 */
	EE.grid_settings = function()
	{
		return new GridSettings();
	};

})(jQuery);