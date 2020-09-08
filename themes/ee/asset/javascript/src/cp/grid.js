/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

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
	 * 'displaySettings' - When settings form is displayed
	 *
	 * @param	{string}	fieldtypeName	Class name of fieldtype so the
	 *				correct cell object can be passed to the handler
	 * @param	{string}	action			Name of action
	 * @param	{func}		func			Callback function for event
	 */
	bind: function(fieldtypeName, action, func) {
		if (this._eventHandlers[action] == undefined) {
			this._eventHandlers[action] = [];
		}

		// Each fieldtype gets one method per handler
		this._eventHandlers[action][fieldtypeName] = func;
	}
};

/**
 * Grid Publish class
 *
 * @param	{string}	field		Selector of table to instantiate as a Grid
 */
Grid.Publish = function(field, settings) {
	if (field === null || field === undefined || $(field).closest('.fluid-field-templates').size() > 0) {
		return;
	}
	this.root = $(field);
	this.parentContainer = this.root.parents('.fieldset-faux, .fluid-field');
	this.blankRow = $('tr.grid-blank-row', this.root);
	this.emptyField = $('tr.no-results', this.root);
	this.tableActions = $('tr.tbl-action', this.root);
	this.rowContainer = this.root.find('> tbody');
	this.addButtonToolbar = $('ul.toolbar:has(li.add)', this.parentContainer);
	this.header = null;
	this.isFileGrid = this.root.closest('.js-file-grid').size() > 0;

	this.rowSelector = 'tr';
	this.cellSelector = 'td';
	this.reorderHandleContainerSelector = 'th.reorder-col, td.reorder-col';
	this.deleteContainerHeaderSelector = 'th.grid-remove';
	this.deleteButtonsSelector = 'td:last-child .toolbar .remove:has(a[rel=remove_row])';
	this.sortableParams = {};

	this.settings = (settings !== undefined) ? settings : EE.grid_field_settings[field.id];
	this.init();

	this.eventHandlers = [];
}

Grid.MiniField = function(field, settings) {
	this.root = $(field);
	this.root.data('gridInitialized', true);
	this.parentContainer = this.root;
	this.blankRow = $('.grid-blank-row', this.root);
	this.emptyField = $('.field-no-results', this.root);
	this.tableActions = null;
	this.rowContainer = $('.keyvalue-item-container', this.root);
	this.addButtonToolbar = $('> [rel=add_row]', this.parentContainer);
	this.header = $('.fields-keyvalue-header', this.root);
	this.isFileGrid = false;

	this.rowSelector = '.fields-keyvalue-item';
	this.cellSelector = '.field-control';
	this.reorderHandleContainerSelector = 'ul.toolbar:has(li.reorder)';
	this.deleteContainerHeaderSelector = null;
	this.deleteButtonsSelector = 'ul.toolbar:has(li.remove)';
	this.sortableParams = {
		sortableContainer: '.keyvalue-item-container',
		handle: 'li.reorder',
		cancel: 'li.sort-cancel',
		item: '.fields-keyvalue-item'
	},

	this.settings = settings;
	this.init();
	this._addNewRowOnEnter();

	this.eventHandlers = [];
}

$.fn.miniGrid = function(params) {
	return this.each(function() {
		if ( ! $(this).data('gridInitialized')) {
			return new Grid.MiniField(this, params);
		}
	});
}

Grid.Publish.prototype = Grid.MiniField.prototype = {

	init: function() {
		this._bindSortable();
		this._bindAddButton();
		this._bindDeleteButton();
		this._toggleRowManipulationButtons();
		this._fieldDisplay();

		// Store the original row count so we can properly increment new
		// row placeholder IDs in _addRow()
		this.original_row_count = this._getRows().size();

		// Disable input elements in our blank template container so they
		// don't get submitted on form submission
		this.blankRow.find(':input').attr('disabled', 'disabled');

		// Allow access to this Grid.Publish object from the DOM element;
		// this may be a bad idea
		this.root.data('GridInstance', this)
	},

	/**
	 * When enter is pressed in a text box, we will add a new row to the Grid
	 * and set focus in the first text box in that row
	 *
	 * Note: This listener isn't added by default, typically only used in mini Grid
	 */
	_addNewRowOnEnter: function() {
		var that = this;
		$(this.root).on('keypress', 'input[type=text]', function(event) {
			if (event.keyCode == 13) {
				event.preventDefault();
				that._addRow();

				// Give focus to first text box
				$(that.rowSelector, that.root).last()
					.find('input[type=text]')
					.first()
					.focus();
			}
		});
	},

	/**
	 * Allows rows to be reordered
	 */
	_bindSortable: function() {
		var that = this,
			params = {
				// Fire 'beforeSort' event on sort start
				beforeSort: function(row) {
					that._fireEvent('beforeSort', row);
				},
				// Fire 'afterSort' event on sort stop
				afterSort: function(row) {
					that._fireEvent('afterSort', row);
					$(document).trigger('entry:preview');
				}
			};

		params = $.extend(params, this.sortableParams);

		this.root.eeTableReorder(params);
	},

	/**
	 * Adds rows to a Grid field based on the fields minimum rows setting
	 * and how many rows already exist
	 */
	_addMinimumRows: function() {
		// File Grid minimum row count validated on server
		if (this.isFileGrid) {
			return
		}

		// Figure out how many rows we need to add
		var rowsCount = this._getRows().size(),
			neededRows = this.settings.grid_min_rows - rowsCount;

		// Show empty field message if field is empty and no rows are needed
		if (rowsCount == 0 && neededRows == 0) {
			this._setNoResultsVisible(true)
		}

		// Add the needed rows
		while (neededRows > 0) {
			this._addRow();

			neededRows--;
		}
	},

	/**
	 * Toggles the visibility of the Add button and Delete buttons for rows
	 * based on the number of rows present and the max and min rows settings
	 */
	_toggleRowManipulationButtons: function() {
		var rowCount = this._getRows().size(),
			showControls = rowCount > 0;

		// Show add button below field when there are more than zero rows
		this.addButtonToolbar.toggle(showControls && ! this.isFileGrid);
		$(this.reorderHandleContainerSelector, this.root).toggle(showControls);
		$(this.deleteContainerHeaderSelector, this.root).toggle(showControls);

		if (this.header) {
			this.header.toggle(showControls);
		}

		if (this.settings.grid_max_rows !== '') {
			// Show add button if row count is below the max rows setting,
			// and only if there are already other rows present
			this.addButtonToolbar.toggle(rowCount < this.settings.grid_max_rows && rowCount > 0 && ! this.isFileGrid);
			if (this.isFileGrid) {
				this.root.closest('.js-file-grid').find('div[data-file-grid-react]').toggle(rowCount < this.settings.grid_max_rows && rowCount >= 0);
			}
		}

		if (this.settings.grid_min_rows !== '') {
			// Show delete buttons if the row count is above the min rows setting
			$(this.deleteButtonsSelector, this.root).toggle(rowCount > this.settings.grid_min_rows);
		}

		// Do not allow sortable to run when there is only one row, otherwise
		// the row becomes detached from the table and column headers change
		// width in a fluid-column-width table
		$(this.reorderHandleContainerSelector, this.rowContainer).toggleClass('sort-cancel', rowCount == 1);

		// Inside File Grid? Hide Grid completely if there are no rows
		if (this.isFileGrid) {
			this.root.toggleClass('hidden', rowCount == 0)
		}
	},

	/**
	 * Returns current number of data rows in the Grid field, makes sure
	 * to skip counting of blank row, empty row and header row
	 *
	 * @return	{int}	Number of rows
	 */
	_getRows: function() {
		return this.rowContainer.children(this.rowSelector)
			.not(this.blankRow
				.add(this.emptyField)
				.add(this.tableActions)
			);
	},

	/**
	 * Binds click listener to Add button to insert a new row at the bottom
	 * of the field
	 */
	_bindAddButton: function() {
		var that = this;

		$('a[rel=add_row]', this.parentContainer)
			.add('.tbl-action a.add', this.root)
			.on('click', function(event) {
				event.preventDefault();
				that._addRow();
			}
		);
	},

	/**
	 * Inserts new row at the bottom of our field
	 */
	_addRow: function() {
		// Clone our blank row
		el = this.blankRow.clone();

		el.removeClass('grid-blank-row');
		el.removeClass('hidden');

		// Increment namespacing on inputs
		this.original_row_count++;
		el.html(
			el.html().replace(
				RegExp('new_row_[0-9]{1,}', 'g'),
				'new_row_' + this.original_row_count
			)
		);

		// Add the new row ID to the field data
		$('> '+this.cellSelector, el).attr(
			'data-new-row-id',
			'new_row_' + this.original_row_count
		);

		// Enable inputs
		el.find(':input').removeAttr('disabled');

		// Append the row to the end of the row container
		if (this.tableActions && this.tableActions.length) {
			this.tableActions.before(el);
		} else {
			this.rowContainer.append(el);
		}

		// Make sure empty field message is hidden
		this._setNoResultsVisible(false)

		// Hide/show delete buttons depending on minimum row setting
		this._toggleRowManipulationButtons();

		// Fire 'display' event for the new row
		this._fireEvent('display', el);
		$(document).trigger('entry:preview');

		$(this.root).trigger('grid:addRow', el);

		// Bind the new row's inputs to AJAX form validation
		if (EE.cp && EE.cp.formValidation !== undefined) {
			EE.cp.formValidation.bindInputs(el);
		}

		return el;
	},

	/**
	 * Binds click listener to Delete button in row column to delete the row
	 */
	_bindDeleteButton: function() {
		var that = this;

		this.root.on('click', 'a[rel=remove_row]', function(event) {
			event.preventDefault();

			var row = $(this).closest(that.rowSelector);

			// Fire 'remove' event for this row
			that._fireEvent('remove', row);
			$(document).trigger('entry:preview');

			// Remove the row
			row.remove();

			that._toggleRowManipulationButtons();

			// Show our empty field message if we have no rows left
			if (that._getRows().size() == 0) {
				that._setNoResultsVisible(true);
			}

			// Mark entire Grid field as valid if all rows with invalid cells are cleared
			if ($('td.invalid', that.root).size() == 0 &&
				EE.cp &&
				EE.cp.formValidation !== undefined) {
				EE.cp.formValidation.markFieldValid($('input, select, textarea', that.blankRow).eq(0));
			}
		});
	},

	/**
	 * Set visibility status of No Results row
	 */
	_setNoResultsVisible: function(visible) {
		this.emptyField.toggleClass('hidden', ! visible)

		// An input may be present to keep the field in POST when empty
		if (visible) {
			this.emptyField.find(':input').removeAttr('disabled')
		} else {
			this.emptyField.find(':input').attr('disabled', 'disabled')
		}
	},

	/**
	 * Called after main initialization to fire the 'display' event
	 * on pre-exising rows
	 */
	_fieldDisplay: function() {
		var that = this;

		setTimeout(function(){
			that._getRows().each(function() {
				that._fireEvent('display', $(this));
			});

			that._addMinimumRows();
		}, 500);
	},

	/**
	 * Fires event to fieldtype callbacks
	 *
	 * @param	{string}		action	Action name
	 * @param	{jQuery object}	row		jQuery object of affected row
	 */
	_fireEvent: function(action, row) {
		// If no events regsitered, don't bother
		if (Grid._eventHandlers[action] === undefined) {
			return;
		}

		// For each fieldtype binded to this action
		for (var fieldtype in Grid._eventHandlers[action]) {
			// Find the sepecic cell(s) for this fieldtype and send each
			// to the fieldtype's event hander
			row.find('td[data-fieldtype="'+fieldtype+'"]').each(function() {
				Grid._eventHandlers[action][fieldtype]($(this));
			});
		}
	}
};

/**
 * Grid Settings class
 */
Grid.Settings = function(root, settings) {
	this.root = root || $('.fields-grid-setup[data-group=grid]');
	this.settings = settings || { minColumns: 1, fieldName: 'grid' };
	this.colTemplateContainer = $('.'+this.settings.fieldName+'-col-settings-elements');
	this.blankColumn = this.colTemplateContainer.find('.fields-grid-item');
	this.noResults = this.root.find('.field-no-results');

	this.init();
}

Grid.Settings.prototype = {

	init: function() {
		this._bindSortable();
		this._bindExpandButton();
		this._expandErroredColumns();
		this._bindActionButtons(this.root);
		this._toggleDeleteButtons();
		this._toggleNoResults();
		this._bindColTypeChange();

		// If this is a new field, bind the automatic column title plugin
		// to the first column
		this._bindAutoColName(this.root.find('div.fields-grid-item[data-field-name^="new_"]'));

		// Fire displaySettings event
		this._settingsDisplay();

		// Disable input elements in our blank template container so they
		// don't get submitted on form submission
		this.colTemplateContainer.find(':input').attr('disabled', 'disabled');
	},

	/**
	 * Allows columns to be reordered
	 */
	_bindSortable: function() {
		this.root.sortable({
			axis: 'y',
			containment: 'parent',
			handle: '.fields-grid-tool-reorder',
			items: '.fields-grid-item',
			sort: EE.sortable_sort_helper
		});

		$(this.root).on('click', '.fields-grid-tool-reorder', function(e){
			e.preventDefault();
		});
	},

	/**
	 * Binds expand button in column toolbar
	 */
	_bindExpandButton: function() {
		var that = this;
		$(this.root).on('click', '.fields-grid-tool-expand', function(e) {
			that._toggleColumnExpand($(this).parents('.fields-grid-item'))
			e.preventDefault()
		});
	},

	/**
	 * For any columns that have validation errors, expand them
	 */
	_expandErroredColumns: function() {
		var that = this;
		$('.fields-grid-item', this.root).each(function(i, column) {
			if ($('.fieldset-invalid', column).size()) {
				that._toggleColumnExpand($(column), true)
			}
		})
	},

	/**
	 * Toggle collapsed state of column settings
	 *
	 * @param	{jQuery Object}	column	Object to find action buttons in to bind
	 * @param	{boolean}		state	Optional, whether or not to expand (true) or collapse (false)
	 */
	_toggleColumnExpand: function(column, state) {
		var openClass = 'fields-grid-item---open',
			isOpen = column.hasClass(openClass),
			toggleHeader = $('.toggle-header', column),
			toggleHeaderFieldName = $('b', toggleHeader),
			toggleHeaderFieldType = $('span.txt-fade', toggleHeader),
			colLabel = $('input[name$="[col_label]"]', column).val(),
			colType = $('input[name$="[col_type]"]', column).val()

		if (isOpen) {
			toggleHeaderFieldName.html(colLabel)
			toggleHeaderFieldType.html('('+colType+')')
		}

		column.toggleClass(openClass, state !== undefined ? state : ! isOpen)
	},

	/**
	 * Convenience method for binding column manipulation buttons (add, copy, remove)
	 * for a given context
	 *
	 * @param	{jQuery Object}	context	Object to find action buttons in to bind
	 */
	_bindActionButtons: function(context) {
		this._bindAddButton(context);
		this._bindCopyButton(context);
		this._bindDeleteButton(context);
	},

	/**
	 * Binds click listener to Add button to insert a new column at the end
	 * of the columns
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
	_bindAddButton: function(context) {
		var that = this;

		context.find('.fields-grid-tool-add')
			.add(that.noResults.find('a[rel=add_new]'))
			.on('click', function(event) {
				event.preventDefault();

				that.noResults.hide();

				var parentCol = $(this).parents('.fields-grid-item');

				that._insertColumn(that._buildNewColumn(), parentCol);
			});
	},

	/**
	 * Binds click listener to Copy button in each column to clone the column
	 * and insert it after the column being cloned
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
	_bindCopyButton: function(context) {
		var that = this;

		context.find('.fields-grid-tool-copy').off('click').on('click', function(event) {
			event.preventDefault();

			var parentCol = $(this).parents('.fields-grid-item');

			// Collapse cloned column
			that._toggleColumnExpand(parentCol, false)

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
	 *
	 * @param	{jQuery Object}	context		Object to find action buttons in to bind
	 */
	_bindDeleteButton: function(context) {
		var that = this;

		context.on('click', '.fields-grid-tool-remove', function(event) {
			event.preventDefault();

			var settings = $(this).parents('.fields-grid-item');

			// Only animate column deletion if we're not deleting the last column
			if (settings.index() == $('.fields-grid-item:last', that.root).index()) {
				settings.remove();
				that._toggleDeleteButtons();
				that._toggleNoResults();
			} else {
				settings.animate({
					opacity: 0
				}, 200, function() {
					// Clear HTML before resize animation so contents don't
					// push down bottom of column container while resizing
					settings.html('');

					settings.animate({
						height: 0
					}, 200, function() {
						settings.remove();
						that._toggleDeleteButtons();
						that._toggleNoResults();
					});
				});
			}

			// Trigger validation on any invalid inputs in case the validaiton
			// errors were due to a duplicate column name/label in this column
			var invalidFields = $('fieldset.fieldset-invalid input', that.root)
			if (invalidFields.size()) {
				invalidFields.trigger('blur')
			} else {
			// Or, the deleted column contained the only validation errors, trigger
			// validation on another known field to re-enable submit buttons
				$('input[name=field_name]').trigger('blur')
			}
		});
	},

	/**
	 * Toggles the delete buttons on the columns if there are more columns than
	 * the minColumns setting, also handles showing no
	 */
	_toggleDeleteButtons: function() {
		var moreThanMinimum = this._getColumnCount() > this.settings.minColumns,
			deleteButtons = this.root.find('.fields-grid-tool-remove');

		deleteButtons.toggle(moreThanMinimum);
	},

	/**
	 * Toggles No Results message based on existence of columns
	 */
	_toggleNoResults: function() {
		this.noResults.toggle(this._getColumnCount() == 0)
	},

	/**
	 * Get the number of columns for this Grid field
	 */
	_getColumnCount: function() {
		return this.root.find('.fields-grid-item:visible').size()
	},

	/**
	 * Inserts a new column after a specified element
	 *
	 * @param	{jQuery Object}	column		Column to insert
	 * @param	{jQuery Object}	insertAfter	Element to insert the column
	 *				after; if left blank, defaults to last column
	 */
	_insertColumn: function(column, insertAfter) {
		var lastColumn = $('.fields-grid-item:last', this.root);

		// Default to inserting after the last column
		if (insertAfter == undefined) {
			insertAfter = lastColumn;
		}

		// If we're inserting a column in the middle of other columns,
		// animate the insertion so it's clear where the new column is
		if (insertAfter.index() != lastColumn.index()) {
			column.css({ opacity: 0 })
		}

		if (insertAfter.length) {
			column.insertAfter(insertAfter)
		} else {
			this.root.append(column)
		}

		this._toggleDeleteButtons();

		column.animate({
			opacity: 1
		}, 400);

		// Bind automatic column name
		this._bindAutoColName(column);

		// Bind column manipulation buttons
		this._bindActionButtons(column);

		// Bind AJAX form validation
		EE.cp.formValidation.bindInputs(column);

		// Bind column type dropdown component
		Dropdown.renderFields(column)

		// Expand column
		this._toggleColumnExpand(column, true)

		// Scroll to new element
		$('body').animate({ scrollTop: column.offset().top - 10 }, 500)

		// Fire displaySettings event
		this._fireEvent('displaySettings', $('.grid-col-settings-custom > div', column));
	},

	/**
	 * Binds ee_url_title plugin to column label box to auto-populate the
	 * column name field; this is only applied to new columns
	 *
	 * @param	{jQuery Object}	el	Column to bind ee_url_title to
	 */
	_bindAutoColName: function(columns) {
		columns.each(function(index, column) {
			$('input[name$="\\[col_label\\]"]', column).bind('keyup keydown', function() {
				$(this).ee_url_title($(column).find('input[name$="\\[col_name\\]"]'), true);
			});
		});
	},

	/**
	 * Builds new column from scratch or based on an existing column
	 *
	 * @param	{jQuery Object}	el	Column to base new column off of, when
	 *				copying an existing column for example; if left blank,
	 *				defaults to blank column
	 * @return	{jQuery Object}	New column element
	 */
	_buildNewColumn: function(el) {
		if (el == undefined) {
			el = this.blankColumn.clone();
		} else {
			// Clone our example column
			el = this._cloneWithFormValues(el);

			// Make sure Dropdown component initializes with cloned selection
			var colType = el.find('input[name*=col_type]').val()
			el.find('div[data-dropdown-react]').attr('data-initial-value', colType)
		}

		// Clear out column name field in new column because it has to be unique
		el.find('input[name$="\\[col_name\\]"]').attr('value', '');

		// Need to make sure the new column's field names are unique
		var new_namespace = 'new_' + $('.fields-grid-item', this.root).size();
		var old_namespace = el.data('field-name');

		el.html(
			this._swapNamespace(el.html(), old_namespace, new_namespace)
		)

		el.attr('data-field-name', new_namespace);

		// Make sure inputs are enabled if creating blank column
		el.find(':input').removeAttr('disabled').removeClass('grid_settings_error');

		return el;
	},

	/**
	 * Binds change listener to the data type columns dropdowns of each column
	 * so we can load the correct settings form for the selected fieldtype
	 */
	_bindColTypeChange: function() {
		var that = this;

		this.root.on('change', 'input[name$="\\[col_type\\]"]', function(event) {
			// New, fresh settings form
			var settings = that.colTemplateContainer
				.find('.grid_col_settings_custom_field_'+$(this).val()+':last')
				.clone();

			// Enable inputs
			settings.find(':input').removeAttr('disabled');

			var customSettingsContainer = $(this)
				.parents('.fields-grid-item')
				.find('.grid-col-settings-custom');

			var new_namespace = customSettingsContainer
				.parents('.fields-grid-item')
				.attr('data-field-name');
			var old_namespace = '(new_)?[0-9]{1,}';

			// Namespace fieldnames for the current column
			settings.html(
				that._swapNamespace(settings.html(), old_namespace, new_namespace)
			)

			// Find the container holding the settings form, replace its contents
			customSettingsContainer.html(settings);

			// Fire displaySettings event
			that._fireEvent('displaySettings', settings);
		});
	},

	/**
	 * Clones an element and copies over any form input values because
	 * normal cloning won't handle that
	 *
	 * @param	{string}	html			HTML on which to perform the replacement
	 * @param	{string}	oldNamespace	Old namespace
	 * @param	{string}	newNamespace	New namespace
	 * @return	{string}	HTML with new namespaces in place
	 */
	_swapNamespace: function(html, oldNamespace, newNamespace) {
		return html.replace(
				RegExp('name="'+this.settings.fieldName+'\\[cols\\]\\[' + oldNamespace + '\\]', 'g'),
				'name="'+this.settings.fieldName+'[cols][' + newNamespace + ']'
			).replace(
				RegExp('data-input-value="'+this.settings.fieldName+'\\[cols\\]\\[' + oldNamespace + '\\]', 'g'),
				'data-input-value="'+this.settings.fieldName+'[cols][' + newNamespace + ']'
			)
	},

	/**
	 * Clones an element and copies over any form input values because
	 * normal cloning won't handle that
	 *
	 * @param	{jQuery Object}	el	Element to clone
	 * @return	{jQuery Object}	Cloned element with form fields populated
	 */
	_cloneWithFormValues: function(el) {
		var cloned = el.clone();

		el.find(":input:enabled").each(function() {
			// Find the new input in the cloned column for editing
			var new_input = cloned.find(":input[name='"+$(this).attr('name')+"']:enabled");

			if ($(this).is("select")) {
				new_input
					.find('option')
					.removeAttr('selected')
					.filter('[value="'+$(this).val()+'"]')
					.attr('selected', 'selected');
			}
			// Handle checkboxes
			else if ($(this).attr('type') == 'checkbox') {
				// .prop('checked', true) doesn't work, must set the attribute
				if ($(this).prop('checked')) {
					new_input.attr('checked', 'checked');
				}
			}
			// Handle radio buttons
			else if ($(this).attr('type') == 'radio') {
				new_input = new_input
					.filter("[value='"+$(this).val()+"']")
					.removeAttr('checked');

				if ($(this).prop('checked')) {
					new_input.attr('checked', 'checked');
					console.log(new_input);
				}
			}
			// Handle textareas
			else if ($(this).is("textarea")) {
				new_input.html($(this).val());
			}
			// Everything else should handle the value attribute
			else {
				// .val('new val') doesn't work, must set the attribute
				new_input.attr('value', $(this).val());
			}
		});

		return cloned;
	},

	/**
	 * Called after main initialization to fire the 'display' event
	 * on pre-exising columns
	 */
	_settingsDisplay: function() {
		var that = this;
		this.root.find('.fields-grid-item').each(function() {
			// Fire displaySettings event
			that._fireEvent('displaySettings', $('.grid-col-settings-custom > div', this));
		});
	},

	/**
	 * Fires event to fieldtype callbacks
	 *
	 * @param	{string}		action	Action name
	 * @param	{jQuery object}	el		jQuery object of affected element
	 */
	_fireEvent: function(action, el) {
		var fieldtype = el.data('fieldtype');

		// If no events regsitered, don't bother
		if (Grid._eventHandlers[action] === undefined ||
			Grid._eventHandlers[action][fieldtype] == undefined) {
			return;
		}

		Grid._eventHandlers[action][fieldtype]($(el));
	}
};

/**
 * Public method to instantiate Grid field
 */
EE.grid = function(field, settings) {
	if (settings == undefined) {
		settings = $(field).data('grid-settings');
	}

	return new Grid.Publish(field, settings);
};

/**
 * Public method to instantiate Grid settings
 */
EE.grid_settings = function(root, settings) {
	return new Grid.Settings(root, settings);
};

if (typeof _ !== 'undefined' && EE.grid_cache !== 'undefined') {
	_.each(EE.grid_cache, function(args) {
		Grid.bind.apply(Grid, args);
	});
}

$(document).ready(function () {
	FluidField.on('grid', 'add', function(el) {
  		EE.grid($('table', el));
	});
});

})(jQuery);
