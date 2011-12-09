/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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