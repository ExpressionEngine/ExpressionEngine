/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */


/**
 * This jQuery plugin toggles all checkboxes in a table column when a checkbox
 * in a table header is clicked
 *
 * Example usage:
 *	$('table').toggle_all();
 */
(function ($) {
	$.fn.toggle_all = function() {

		// small abstraction for row / column access. We have it in here
		// so that developers don't need to account for datatables changes.
		function Cache($table) {
			var rows = $table.find('tbody tr').get();

			// bind to table events
			if ($table.data('table_config')) {
				$table.bind('tableupdate', function() {
					rows = $table.table('get_current_data').html_rows;
					$table.find('input:checkbox')
						.prop('checked', false)
						.trigger('change');
				});
			}

			// we always need columns ...
			this.getColumn = function(column) {
				return $.map(rows, function(v, i) {
					if ($(v.cells[column]).has('input[type=checkbox]').length) {
						return v.cells[column];
					};
				});
			};
		}

		// Handle shift+clicks for multiple checkbox selection
		var shiftClick = {
			$table: '',
			rowCache: '',
			column: 0,
			tableCells: [],
			shift: false,

			init: function($table, rowCache, column){
				this.$table = $table;
				this.rowCache = rowCache;
				this.column = column;
				this.tableCells = this.rowCache.getColumn(this.column);
				this.checkboxListen();
				this.tableListen();
				this.shiftListen();
			},

			/**
			 * Listens for clicks on the checkboxes of the passed in table cells
			 */
			checkboxListen: function(){
				var that = this;

				$(this.tableCells).each(function(index, el) {
					$(this).find('input[type=checkbox]:enabled').unbind('click').click(function(event) {
						currentlyChecked = that.checkboxChecked(index);
						if (that.shift && currentlyChecked !== false) {
							var low  = (currentlyChecked > index) ? index : currentlyChecked,
								high = (currentlyChecked > index) ? currentlyChecked : index;
							$(that.tableCells).slice(low, high).find('input[type=checkbox]:enabled')
								.attr('checked', true)
								.trigger('change');
						}
					});
				});
			},

			/**
			 * Listen for changes to the table, recache the tableCells and
			 * rebind the checkboxes
			 */
			tableListen: function(){
				var that = this;
				this.$table.bind('tableupdate', function() {
					that.tableCells = that.rowCache.getColumn(that.column);
					that.checkboxListen();
				});
			},

			/**
			 * Listen for the shift button and store the state
			 */
			shiftListen: function(){
				var that = this;
				$(window).bind('keyup keydown', function(event) {
					that.shift = event.shiftKey;
				});
			},

			/**
			 * Check to see what the index of the first checked checkbox is, if
			 * its the only checkbox checked, then return false
			 *
			 * @param {integer} current The index of the clicked checkbox
			 * @return {mixed} Either false if there's only one checkbox checked
			 *                        or the index of the other checked checkbox
			 */
			checkboxChecked: function(current){
				if ($(this.tableCells).find('input[type=checkbox]').not(':eq('+current+')').find(':checked').length > 1) {
					return false;
				}

				var firstIndex = 0;

				$(this.tableCells).each(function(index, el) {
					if (index !== current && $(this).find('input[type=checkbox]').is(':checked')) {
						firstIndex = index;
						return false;
					}
				});

				return firstIndex;
			}
		};

		// GO GO GO
		// Standard jquery plugin procedure
		// Process all matched tables

		return this.each(function() {

			// Simple object to hold header objects
			var headerCheckboxes = {
				checkboxes: {},
				// Add a checkbox, no way to overwrite
				add: function(column_number, checkbox){
					// Make sure an array exists
					if (typeof this.checkboxes[column_number] == 'undefined') {
						this.checkboxes[column_number] = [];
					}

					this.checkboxes[column_number].push(checkbox);
					return true;
				},

				// Get an array of checkboxes for a given column
				get: function(column_number){
					return this.checkboxes[column_number];
				},

				// Iterate over a column of checkboxes
				each: function(column_number, callback){
					$.each(this.checkboxes[column_number], function(index, value) {
						callback.call($(value), index, value);
					});
				},
			};

			var $table = $(this),
				row_cache = new Cache($table);

			// STEP 1:
			// Loop through each selected header with a checkbox
			// Listens to clicks on the checkbox and updates the
			// row below to match its state.

			$table.find('th').has('input:checkbox').each(function(index, val) {
				// Name the table header, figure out it's index, get the header
				// checkbox, and select all the data
				var column = this.cellIndex,
					$header_checkbox = $(this).find(':checkbox');

				// Listen for clicks to the header checkbox
				$(this).on('click', 'input[type=checkbox]', function(event) {
					var checked = $header_checkbox.prop('checked');

					if (event.target != $header_checkbox.get(0)) {
						checked = ! checked;
						$header_checkbox
							.prop('checked', checked)
							.trigger('change');
					}

					// Check all normal checkboxes
					$(row_cache.getColumn(column)).find(':checkbox:enabled')
						.prop('checked', checked)
						.trigger('change');

					// Check all header checkboxes
					headerCheckboxes.each(column, function() {
						$(this)
							.prop('checked', checked)
							.trigger('change');
					});
				});

				// remember the headers
				headerCheckboxes.add(column, $header_checkbox);
				shiftClick.init($table, row_cache, column);
			});


			// STEP 2:
			// Listens to clicks on any checkbox in one of the
			// checkbox columns and update the header checkbox's
			// state to reflect the overall column.

			$table.delegate('td', 'click', function(event) {
				var column = this.cellIndex,
					all_checked = true,
					$header_checkbox;

				// does this column even have a header checkbox?
				// was the click on a checkbox?
				if ( ! headerCheckboxes.get(column) || ! $(event.target).is(':checkbox')) {
					return true;
				}

				if ( ! event.target.checked) {
					// unchecked one, definitely not all checked
					headerCheckboxes.each(column, function(index, element) {
						$(this)
							.prop('checked', false)
							.trigger('change');
					});
					return true;
				}

				// run through the entire column to see if they're
				// all checked or not
				$.each(row_cache.getColumn(column), function() {
					if ( ! $(this).find(':checkbox').prop('checked')) {
						all_checked = false;
						return false;
					}
				});

				// set the header checkbox
				headerCheckboxes.each(column, function(index, element) {
					$(this)
						.prop('checked', all_checked)
						.trigger('change');
				});
			});
		});
	};
})(jQuery);
