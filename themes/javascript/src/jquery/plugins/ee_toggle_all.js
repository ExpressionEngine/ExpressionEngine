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
		$table.find('th input[type=checkbox]').each(function(index, val) {

			// Name the checkbox and its header, get the column index,
			// and select all the td elements
			var $header_checkbox = $(this),
				$header = $(this).closest('th'),
				column = $header.index(),
				$table_data = $table.find('td:nth-child(' + (column + 1) + ')');

			// Listen for clicks to the header/checkbox
			$header.click(function(event) {
				var checked = $header_checkbox.prop('checked');

				// Toggle the actual checkbox if only the header was clicked
				if (event.target.tagName == "TH") {
					checked = ! checked;
					$header_checkbox.prop('checked', checked);
				}

				$table_data.find('input').each(function(){
					$(this).prop('checked', checked);
				});
			});


			// Also listen to checks on the normal checkboxes/TDs, to see if all of
			// them have been (un)checked
			$table_data.click(function(event) {

				// Toggle the actual checkbox if only the td was clicked
				if (event.target.tagName == "TD") {
					$(this).find('input').each(function(){
						$(this).prop('checked', ! $(this).prop('checked'));
					});
				}

				if ($table_data.size() == $table.find('td:nth-child('+ (column + 1) +') input[type=checkbox]:checked').size()) {
					$header_checkbox.prop('checked', true);
				} else if ($table.find('td:nth-child('+ (column + 1) +') input[type=checkbox]:checked').size() == 0) {
					$header_checkbox.prop('checked', false);
				};
			});
		});
	});
};