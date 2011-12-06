/**
 * This jQuery plugin toggles all checkboxes in a table column when a checkbox 
 * in a table header is clicked
 *
 * Example usage:
 *	$('table').toggle_all();
 */
$.fn.toggle_all = function() {
	return this.each(function() {
		// Assuming this is a table
		var $table = $(this);
		
		// Loop through each selected header with a checkbox
		$table.find('th:has(input[type=checkbox])').each(function(index, val) {
			
			// Name the table header, figure out it's index, get the header
			// checkbox, and select all the data
			var $table_header = $(this),
				column = $table_header.index(),
				$header_checkbox = $table_header.find('input[type=checkbox]'),
				$table_data = $table.find('td:nth-child(' + (column + 1) + ') input[type=checkbox]');

			// Listen for clicks to the header checkbox
			$header_checkbox.click(function(event) {
				var checked = $(this).prop('checked');
				$table_data.prop('checked', checked);
			});

			// Also listen to checks on the normal checkboxes, to see if all of
			// them have been (un)checked
			$table_data.click(function(event) {
				if ($table_data.size() == $table.find('td:nth-child('+ (column + 1) +') input[type=checkbox]:checked').size()) {
					$header_checkbox.prop('checked', true);
				} else if ($table.find('td:nth-child('+ (column + 1) +') input[type=checkbox]:checked').size() == 0) {
					$header_checkbox.prop('checked', false);
				};
			});
		});
	});
};