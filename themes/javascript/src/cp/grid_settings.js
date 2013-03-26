(function($) {

	function GridSettings()
	{
		this.root = $('#grid_settings');
		this.settingsScroller = this.root.find('#grid_col_settings_container');
		this.settingsContainer = this.root.find('#grid_col_settings_container_inner');
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
			this._bindColTypeChange();

			// Disable input elements in our blank template container so they
			// don't get submitted on form submission
			this.colTemplateContainer.find(':input').attr('disabled', 'disabled');
		},

		/**
		 * Upon page load, we need to resize the column container to match the
		 * width of the page, minus the width of the labels on the left
		 */
		_bindResize: function()
		{
			var that = this;

			$(document).ready(function()
			{
				that.settingsScroller.width(
					that.root.width() - that.root.find('#grid_col_settings_labels').width()
				);

				// Now, resize the inner container to fit the number of columns
				// we have ready on page load
				that._resizeColContainer();
			});
		},

		/**
		 * Resizes column container based on how many columns it contains
		 *
		 * @param	{boolean}	animated	Whether or not to animate the resize
		 */
		_resizeColContainer: function(animated)
		{
			this.settingsContainer.animate(
			{
				width: this._getColumnsWidth()
			},
			(animated == true) ? 400 : 0);
		},

		/**
		 * Calculates total width the columns in the container should take up,
		 * plus a little padding for the Add button
		 *
		 * @return	{int}	Calculated width
		 */
		_getColumnsWidth: function()
		{
			var columns = this.root.find('.grid_col_settings');

			// 75px of extra room for the add button
			return columns.size() * columns.width() + 75;
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

				var settings = $(this).parents('.grid_col_settings');

				// Only animate column deletion if we're not deleting the last column
				if (settings.index() == $('#grid_settings .grid_col_settings:last').index())
				{
					settings.remove();
					that._resizeColContainer(true);
					that._toggleDeleteButtons();
				}
				else
				{
					settings.animate({
						opacity: 0
					}, 200, function()
					{
						settings.animate({
							width: 0
						}, 200, function()
						{
							settings.remove();
							that._resizeColContainer(true);
							that._toggleDeleteButtons();
						});
					});
				}
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
			lastColumn = $('#grid_settings .grid_col_settings:last');

			// Default to inserting after the last column
			if (insertAfter == undefined)
			{
				insertAfter = lastColumn;
			}

			// If we're inserting a column in the middle of other columns,
			// animate the insertion so it's clear where the new column is
			if (insertAfter.index() != lastColumn.index())
			{
				column.css({ opacity: 0 })
			}

			column.insertAfter(insertAfter);

			this._resizeColContainer();
			this._toggleDeleteButtons();

			// If we are inserting a column after the last column, scroll to
			// the end of the column container
			if (insertAfter.index() == lastColumn.index())
			{
				// Scroll container to the very end
				this.settingsScroller.animate({
					scrollLeft: this._getColumnsWidth()
				}, 700);
			}

			column.animate({
				opacity: 1
			}, 400);
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
				el_clone = el.clone();

				// Data input by the user since the page was loaded may not have
				// been cloned, so we have to manually repopulate the fields in
				// the cloned column
				el.find(":input").each(function()
				{
					// Find the new input in the cloned column for editing
					var new_input = el_clone.find(":input[name='"+$(this).attr('name')+"']");

					if ($(this).is("select"))
					{
						new_input
							.find('option')
							.removeAttr('selected')
							.filter('[value="'+$(this).val()+'"]')
							.attr('selected', 'selected');
					}
					// Handle checkboxes
					else if ($(this).attr('type') == 'checkbox')
					{
						// .prop('checked', true) doesn't work, must set the attribute
						new_input.attr('checked', $(this).attr('checked'));
					}
					// Handle radio buttons
					else if ($(this).attr('type') == 'radio')
					{
						new_input
							.removeAttr('selected')
							.filter("[value='"+$(this).val()+"']")
							.attr('checked', $(this).attr('checked'));
					}
					// Handle textareas
					else if ($(this).is("textarea"))
					{
						new_input.html($(this).val());
					}
					// Everything else should handle the value attribute
					else
					{
						// .val('new val') doesn't work, must set the attribute
						new_input.attr('value', $(this).val());
					}
				});

				el = el_clone;
			}

			// Clear out column name field in new column because it has to be unique
			el.find('input[name$="\\[name\\]"]').attr('value', '');

			// Need to make sure the new columns field names are unique
			el.html(
				el.html().replace(
					RegExp('(new_|col_id_)[0-9]{1,}', 'g'), 'new_' + $('.grid_col_settings').size()
				)
			);

			// Make sure inputs are enabled if creating blank column
			el.find(':input').removeAttr('disabled');

			return el;
		},

		/**
		 * Binds change listener to the data type columns dropdowns of each column
		 * so we can load the correct settings form for the selected fieldtype
		 */
		_bindColTypeChange: function()
		{
			var that = this;

			this.root.on('change', '.grid_data_type .grid_col_select', function(event)
			{
				// New, fresh settings form
				var settings = that.colTemplateContainer
					.find('.grid_col_settings_custom_field_'+$(this).val()+':last')
					.clone();

				// Enable inputs
				settings.find(':input').removeAttr('disabled');

				// Find the container holding the settings form, replace its contents
				$(this).parents('.grid_col_settings')
					.find('.grid_col_settings_custom')
					.html(settings);
			});
		}
	};

	/**
	 * Public method to instantiate
	 */
	EE.grid_settings = function()
	{
		return new GridSettings();
	};

})(jQuery);