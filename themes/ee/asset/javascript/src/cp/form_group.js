/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

"use strict";

// fields that are children of hidden parent fields
var hidden = {
	"always-hidden": false
};

// real visibility states of hidden children
var states = {
	"always-hidden": false
};


$(document).ready(function() {

	var fields = $('*[data-group-toggle]:radio');

	toggleInputs(fields, '', false);

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
			if (states[data] == undefined || states[data] == false) {
				states[data] = !!(key == value);
			}
		});
	});

	// next go through and trigger our toggle on each to get the
	// correct initial states. this cannot be combined with the
	// above loop.
	$('*[data-group-toggle]').each(function(index, el) {

		if ($(this).is(':radio') && ! $(this).is(':checked')) {
			return;
		}

		//only respect the state of toggles that are not currently hidden
		if ($(this).parents('fieldset').is(':visible')) {
			EE.cp.form_group_toggle(this);
		}

		var config = $(this).data('groupToggle');

		// Initially, if there are radio buttons across multiple groups
		// that share the same name, only the last one specified to be
		// checked will be checked, so we need to prefix those inputs
		// in form_group_toggle and then tell the browser to populate
		// the radio buttons with their default checked state
		/*
		$.each(config, function (key, data) {
			var elements = $('*[data-group="'+data+'"]');

			elements.find(':radio').each(function() {
				$(this).prop('checked', $(this).attr('checked') == 'checked');
			});
		});
		*/
	});
});

function toggleFields(fields, show, key) {
	toggleInputs(fields, key, show);
	fields.toggle(show);

	fields.each(function(i, field) {
		var fieldset = $(field).closest('fieldset');

		if (fieldset.hasClass('fieldset-invalid')) {
			if (fieldset.find('input:visible').not('.button').length == 0) {
				fieldset.removeClass('fieldset-invalid');
				fieldset.find('em.ee-form-error-message').remove();
			}
		}
	});
}

function toggleSections(sections, show, key) {
	sections.each(function() {
		$(this).toggle(show);
		$(this).nextUntil('h2, .form-btns').each(function() {

			var field = $(this),
				group = field.data('group');

			// if we're showing this section, but the field is hidden
			// from another toggle, then don't show it
			// if (group && group != key) {
			// 	hidden[group] = ! show;
			// }

			if (show && group && hidden[group] != undefined) {
				toggleFields(field, hidden[group], key);
			} else {
				toggleFields(field, show, key);
			}
		});
	});
}

EE.cp.form_group_toggle = function(element) {

	if ( ! $(element).length) {
		return;
	}

	var config = $(element).data('groupToggle'),
		value  = $(element).val();

	states = {
		"always-hidden": false
	};

	var toggle = function (key, data) {
		// Fields can belong to multiple groups, separated by pipes in the data
		// attributes; drill down into those attributes
		var field_targets = $('*[data-group*="'+data+'"]').filter(function(el) {
			return $(this).data('group').split('|').includes(data)
		});
		var section_targets = $('*[data-section-group*="'+data+'"]').filter(function() {
			return $(this).data('sectionGroup').split('|').includes(data)
		});

		if (states[data] == undefined || states[data] == false) {
			states[data] = (key == value);
		}
		toggleFields(field_targets, hidden[data] ? false : (key == value));
		toggleSections(section_targets, states[data], data);
	};

	// Hide all the toggled fields and sections
	$.each(config, function (key, data) {
		if (key != value) {
			toggle(key, data);
		}
	});

	// Show the selected fields and sections
	toggle(value, config[value]);

	window.document.dispatchEvent(
		new CustomEvent('formFields:toggle', {
			detail: {
				group: config[value],
				state: value,
				for: $(element).parent().data('toggle-for')
			} 
		})
	);

	// The reset the form .last values
	var form = $(element).closest('form');
}

EE.cp.fieldToggleDisable = function(context, fieldName) {
	$('fieldset :input:hidden', context)
		.not('.filter-item__search input')
		.not('.search-input__input')
		.not('.fields-grid-item:visible :input') // Don't disable collapsed Grid settings
		.not('.fields-grid-setup:visible .fields-grid-item.hidden :input') // Don't disable phantom Grid columns
		.attr('disabled', true);
	$('fieldset:visible input[type=hidden]', context).attr('disabled', false);

	fieldName = fieldName || 'field_type';
	$('input[name="'+fieldName+'"]', context).on('change', function() {
		$('fieldset :input', context)
			.not('.filter-item__search input')
			.attr('disabled', true);
		$('fieldset:visible :input', context)
			.not('.grid-blank-row :input')
			.attr('disabled', false);
		$('.fields-grid-setup:visible .fields-grid-item.hidden :input')
			.attr('disabled', false);
	});
}

// This all kind of came about from needing to preserve radio button
// state for radio buttons but identical names across various groups.
// In an effort not to need to prefix those input names, we'll handle
// it automatically with this function.
function toggleInputs(container, group_name, enable) {
	container.find(':radio').each(function() {

//		var input = $(this),
//			name = input.attr('name'),
//			clean_name = (name) ? name.replace('el_disabled_'+group_name+'_', '') : '';

		var input = $(this);

		// Disable inputs that aren't shown, we don't need those in POST
		input.attr('disabled', ! enable);

		var state = input.data('el_checked');

		if ( ! state) {
			state = ($(this).attr('checked') == 'checked');
			input.data('el_checked', state);

			input.change(function() {
				input.data('el_checked', input.prop('checked'));
			});
		}

		if (enable) {
			input.prop('checked', state);
		}
		/*
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
		*/
	});
}

})(jQuery);
