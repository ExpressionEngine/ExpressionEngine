/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

(function($) {

"use strict";

$(document).ready(function() {

	$('*[data-group-toggle]').each(function(index, el) {

		if ($(this).is(':radio'))
		{
			if ( ! $(this).is(':checked'))
			{
				return;
			}
		}

		EE.cp.form_group_toggle(this);

		var config = $(this).data('groupToggle');

		// Initially, if there are radio buttons across multiple groups
		// that share the same name, only the last one specified to be
		// checked will be checked, so we need to prefix those inputs
		// in form_group_toggle and then tell the browser to populate
		// the radio buttons with their default checked state
		$.each(config, function (key, data) {

			var elements = $('*[data-group="'+data+'"]');

			elements.find(':radio').each(function() {
				$(this).prop('checked', $(this).attr('checked') == 'checked');
			});
		});
	});

});

EE.cp.form_group_toggle = function(element) {

	var config = $(element).data('groupToggle'),
		value  = $(element).val();

	if ($(element).is(':hidden')) {
		return;
	}

	// First, disable all inputs
	$.each(config, function (key, data) {
		toggleInputs($('*[data-group="'+data+'"]'), key, false);
	});

	// Then show the selected group and enable its inputs
	$.each(config, function (key, data) {
		var group = $('*[data-group="'+data+'"]');
		group.toggle(key == value);

		// if this is a nested one
		group.filter('h2').nextUntil('h2, .form-ctrls').toggle(key == value);

		if (key == value) {
			toggleInputs(group, key, true);
		}

		group.removeClass('last');

		if (key == value) {
			group.last().addClass('last');
			group.filter(':not(h2)').prev('fieldset:visible').removeClass('last');
		} else {
			group.prev('fieldset:visible').addClass('last');
		}

		$('h2').each(function() {
			$(this).prevAll('fieldset:visible').first().addClass('last');
		});
	});

	// This all kind of came about from needing to preserve radio button
	// state for radio buttons but identical names across various groups.
	// In an effort not to need to prefix those input names, we'll handle
	// it automatically with this function.
	function toggleInputs(container, group_name, enable) {
		container.find(':input').each(function() {

			var input = $(this),
				name = input.attr('name'),
				clean_name = (name) ? name.replace('el_disabled_'+group_name+'_', '') : '';

			// Disable inputs that aren't shown, we don't need those in POST
			input.attr('disabled', ! enable);

			// Prefixing the name ensures radio buttons will keep their state
			// when changing the visible group, as well as any JS handlers
			// based on name should take note of and inputs that are no
			// longer in their scope
			if (name) {
				if (enable) {
					input.attr('name', clean_name);
				} else {
					input.attr('name', 'el_disabled_'+group_name+'_'+clean_name);
				}
			}
		});
	}
}

})(jQuery);
