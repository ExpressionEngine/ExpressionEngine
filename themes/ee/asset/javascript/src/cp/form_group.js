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

// fields that are children of hidden parent fields
var hidden = {};

// real visibility states of hidden children
var states = {};


$(document).ready(function() {

	// loop through all of the toggles and record their current state
	// we need this so that we can check if a section's visiblity should
	// override the visibility of a child.
	$('*[data-group-toggle]').each(function(index, el) {

		if ($(this).is(':radio') && ! $(this).is(':checked')) {
			return;
		}

		var config = $(this).data('groupToggle'),
			value  = $(this).val();

		$.each(config, function (key, data) {
			states[data] = !!(key == value);
		});
	});

	// next go through and trigger our toggle on each to get the
	// correct initial states. this cannot be combined with the
	// above loop.
	$('*[data-group-toggle]').each(function(index, el) {

		if ($(this).is(':radio') && ! $(this).is(':checked')) {
			return;
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

function toggleFields(fields, show, key) {
	toggleInputs(fields, key, show);
	fields.toggle(show);
}

function toggleSections(sections, show, key) {
	sections.each(function() {
		$(this).toggle(show);
		$(this).nextUntil('h2, .form-ctrls').each(function() {

			var field = $(this),
				group = field.data('group');

			// if we're showing this section, but the field is hidden
			// from another toggle, then don't show it
			if (group) {
				hidden[group] = ! show;
			}

			if (show && group) {
				toggleFields(field, states[group], key);
			} else {
				toggleFields(field, show, key);
			}
		});
	});
}

EE.cp.form_group_toggle = function(element) {

	var config = $(element).data('groupToggle'),
		value  = $(element).val();

	// Show the selected group and enable its inputs
	$.each(config, function (key, data) {
		var field_targets = $('*[data-group="'+data+'"]');
		var section_targets = $('*[data-section-group="'+data+'"]');

		states[data] = (key == value);
		toggleFields(field_targets, hidden[data] ? false : (key == value));
		toggleSections(section_targets, key == value);
	});

	// The reset the form .last values
	var form = $(element).closest('form');

	form.find('fieldset.last').removeClass('last');
	form.find('h2').each(function() {
		$(this).prevAll('fieldset:visible').first().addClass('last');
	});
}

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

})(jQuery);
