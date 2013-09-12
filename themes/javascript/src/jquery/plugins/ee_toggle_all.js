/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
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
					$table.find('input:checkbox').prop('checked', false);
				});
			}

			// we always need columns ...
			this.getColumn = function(column) {
				return $.map(rows, function(v, i) {
					return v.cells[column];
				});
			};
		}

		// Handle shift+clicks for multiple checkbox selection
		var shiftClick = {
			tableCells: [],
			shift: false,

			init: function(tableCells){
				this.tableCells = tableCells;
				this.shiftListen();
				this.checkboxListen(tableCells);
			},

			/**
			 * Listens for clicks on the checkboxes of the passed in table cells
			 */
			checkboxListen: function(){
				var that = this;

				$(this.tableCells).each(function(index, el) {
					$(this).find('input[type=checkbox]').click(function(event) {
						currentlyChecked = that.checkboxChecked(index);
						if (that.shift && currentlyChecked !== false) {
							var low  = (currentlyChecked > index) ? index : currentlyChecked;
								high = (currentlyChecked > index) ? currentlyChecked : index;
							$(that.tableCells).slice(low, high).find('input[type=checkbox]').attr('checked', true);
						}
					});
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
				if ($(this.tableCells).find('input[type=checkbox]').not(':eq('+current+')').find(':checked').size() > 1) {
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
			},
		};

		// GO GO GO
		// Standard jquery plugin procedure
		// Process all matched tables

		return this.each(function() {

			var $table = $(this),
				header_checkboxes = {},
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
				$(this).click(function(event) {
					var checked = $header_checkbox.prop('checked');

					if (event.target != $header_checkbox.get(0)) {
						checked = ! checked;
						$header_checkbox.prop('checked', checked);
					}

					var cells = row_cache.getColumn(column);
					$(cells).find(':checkbox').prop('checked', checked);
				});

				// remember the headers
				header_checkboxes[column] = $header_checkbox;

				shiftClick.init(row_cache.getColumn(column));
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
				if ( ! header_checkboxes[column] || ! $(event.target).is(':checkbox')) {
					return true;
				}

				if ( ! event.target.checked) {
					// unchecked one, definitely not all checked
					header_checkboxes[column].prop('checked', false);
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
				header_checkboxes[column].prop('checked', all_checked);
			});
		});
	};
})(jQuery);