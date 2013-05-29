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
			this._bindSortable();
			this._bindAddButton();
			this._bindCopyButton();
			this._bindDeleteButton();
			this._toggleDeleteButtons();
			this._bindColTypeChange();
			this._bindSubmit();

			// Disable input elements in our blank template container so they
			// don't get submitted on form submission
			this.colTemplateContainer.find(':input').attr('disabled', 'disabled');
		},

		/**
		 * Upon page load, we need to resize the settings container to match the
		 * width of the page, minus the width of the labels on the left, and also
		 * need to resize the column container to fit the number of columns we have
		 */
		_bindResize: function()
		{
			var that = this;

			$(document).ready(function()
			{
				that._resizeSettingsContainer();

				// Resize settings container on window resize
				$(window).resize(function()
				{
					that._resizeSettingsContainer();
				});

				// Now, resize the inner container to fit the number of columns
				// we have ready on page load
				that._resizeColContainer();
			});
		},

		/**
		 * Resizes the scrollable settings container to fit within EE's settings
		 * table; this is called on page load and window resize
		 */
		_resizeSettingsContainer: function()
		{
			// First need to set container smaller so that it's not affecting the
			// root with; for example, if the user makes the window width smaller,
			// the root with won't change if the settings scroller container doesn't
			// get smaller, thus the container runs off the page
			this.settingsScroller.width(500);

			this.settingsScroller.width(
				this.root.width() - this.root.find('#grid_col_settings_labels').width()
			)
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
		 * Allows columns to be reordered
		 */
		_bindSortable: function()
		{
			this.settingsContainer.sortable({
				axis: 'x',						// Only allow horizontal dragging
				containment: 'parent',			// Contain to parent
				handle: 'div.grid_data_type',	// Set drag handle to the top box
				items: '.grid_col_settings',	// Only allow these to be sortable
				sort: EE.sortable_sort_helper	// Custom sort handler
			});
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
		 * Binds click listener to Delete button in each column to delete the column
		 */
		_bindDeleteButton: function()
		{
			var that = this;

			this.root.on('click', '.grid_button_delete', function(event)
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
						// Clear HTML before resize animation so contents don't
						// push down bottom of column container while resizing
						settings.html('');

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
			var colCount = this.root.find('.grid_col_settings').size(),
				deleteButtons = this.root.find('.grid_button_delete');

			deleteButtons.toggle(colCount > 1);
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
			var lastColumn = $('#grid_settings .grid_col_settings:last');

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
		 * @return	{jQuery Object}	New column element
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
				el = this._cloneWithFormValues(el);
			}

			// Clear out column name field in new column because it has to be unique
			el.find('input[name$="\\[name\\]"]').attr('value', '');

			// Need to make sure the new columns field names are unique
			el.html(
				el.html().replace(
					RegExp('(new_|col_id_)[0-9]{1,}', 'g'),
					'new_' + $('.grid_col_settings').size()
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

				var customSettingsContainer = $(this)
					.parents('.grid_col_settings')
					.find('.grid_col_settings_custom');

				// Namespace fieldnames for the current column
				settings.html(
					settings.html().replace(
						RegExp('(new_|col_id_)[0-9]{1,}', 'g'),
						customSettingsContainer.data('fieldName')
					)
				);

				// Find the container holding the settings form, replace its contents
				customSettingsContainer.html(settings);
			});
		},

		/**
		 * Binds to form submission to pass along the entire HTML for the last
		 * row in the Grid settings table for easy repopulated upon form
		 * validation failing
		 */
		_bindSubmit: function()
		{
			var that = this;

			this.root.parents('form').submit(function()
			{
				grid_html = that._cloneWithFormValues(that.root.parent('#grid_settings_container'));

				$('<input/>', {
					'type': 'hidden',
					'name': 'grid_html',
					'value': '<div id="grid_settings_container">'+grid_html.html()+'</div>'
				}).appendTo(that.root);
			});
		},

		/**
		 * Clones an element and copies over any form input values because
		 * normal cloning won't handle that
		 * 
		 * @param	{jQuery Object}	el	Element to clone
		 * @return	{jQuery Object}	Cloned element with form fields populated
		 */
		_cloneWithFormValues: function(el)
		{
			var cloned = el.clone();

			el.find(":input").each(function()
			{
				// Find the new input in the cloned column for editing
				var new_input = cloned.find(":input[name='"+$(this).attr('name')+"']");

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
			
			return cloned;
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