/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */


// This class handles showing and hiding dropdown menus within the app
var DropdownController = (function() {

	// Setup the required events for showing and hiding dropdowns

	// Handle showing and hiding hoverable dropdowns
	$('.js-dropdown-hover').each(function() {
		var button = this
		var dropdown = getDropdownForElement(button)

		if (!dropdown) {
			return
		}

		var count = 0;
		var tolerance = 200;

		$(button).add(dropdown).mouseenter(function() {
			count++

			showDropdown(dropdown, button)
		}).mouseleave(function() {
			count = Math.max(0, count - 1)

			setTimeout(function() {
				if (count == 0) {
					hideDropdown(dropdown, button)
				}
			}, tolerance);
		});
	})

	// Hoverable dropdowns should be clickable on mobile
	$('body').on('touchstart', '.js-dropdown-hover', function(e) {
		e.preventDefault()

		var dropdown = getDropdownForElement(this)

		if (!dropdown) {
			return
		}

		var dropdownShown = dropdown.classList.contains('dropdown--open')

		hideAllDropdowns()

		if (dropdownShown) {
			hideDropdown(dropdown, button)
		} else {
			showDropdown(dropdown, button)
		}

		return false
	})

	$('body').on('tap', '.js-dropdown-hover', function(e) {
		e.preventDefault()
		return false
	})

	// Toggle dropdowns when clicking on a dropdown toggle button
	document.body.addEventListener('click', (event) => {
		var button = $(event.target).closest('.js-dropdown-toggle').get(0)

		if (!button) {
			return
		}

		var dropdown = getDropdownForElement(button)

		if (!dropdown) {
			return
		}

		var dropdownShown = dropdown.classList.contains('dropdown--open')

		hideAllDropdowns($(dropdown).parents('.dropdown'))

		if (dropdownShown) {
			hideDropdown(dropdown, button)
		} else {
			dropdown._popper.update();
			dropdown._popper.scheduleUpdate();
			showDropdown(dropdown, button)
		}
	})

	// Hide dropdowns when clicking on a hide dropdown button
	document.body.addEventListener('click', (event) => {
		var button = $(event.target).closest('.js-hide-dropdowns').get(0)

		if (!button) {
			return
		}

		hideAllDropdowns()
	})

	// Hide dropdowns when clicking outside of them
	document.addEventListener('click', (event) => {
		if (!$(event.target).closest('.dropdown, .js-dropdown-toggle').length) {
			hideAllDropdowns()
		}
	})

    function hideAllDropdowns(excludeDropdown) {
		$('.has-open-dropdown').removeClass('has-open-dropdown')
		$('.dropdown--open').not(excludeDropdown).removeClass('dropdown--open')
		$('.dropdown-open').removeClass('dropdown-open').removeClass('open');
    }

    function showDropdown(dropdown, button) {
		button.classList.add('dropdown-open')
		$(button).parent().addClass('has-open-dropdown');
		dropdown.classList.add('dropdown--open');

		if (dropdown.classList.contains('js-dropdown-auto-focus-input')) {
			$(dropdown).find('.dropdown__search input').focus();
		}

		dropdown._popper.update();
		dropdown._popper.scheduleUpdate();
    }

    function hideDropdown(dropdown, button) {
		button.classList.remove('dropdown-open')
		button.classList.remove('open')
		$(button).parent().removeClass('has-open-dropdown');
		dropdown.classList.remove('dropdown--open');
	}

	// Refreshes the position of any visible dropdowns
	function updateDropdownPositions() {
		$('.dropdown.dropdown--open').each(function() {
			var dropdown = this

			if (dropdown._popper) {
				dropdown._popper.update();
				dropdown._popper.scheduleUpdate();
			}
		})
	}

 	// Gets a dropdown for a element, and makes sure its initialized
	function getDropdownForElement(element) {
		var dropdown = $(element).next('.dropdown').get(0) || $(`[data-dropdown='${element.dataset.toggleDropdown}']`).get(0) || $(element).parent('.colorpicker__inner_wrapper').next('.colorpicker__panel').get(0)

		// Should the dropdown be moved to the root of the page?
		var useRoot = element.dataset.dropdownUseRoot || false

		// Does the dropdown exist?
		if (!dropdown) {
			if (useRoot && element._dropdown) {
				return element._dropdown
			}

			return null
		}

		if (useRoot) {
			element._dropdown = dropdown
			$(dropdown).appendTo(document.body);
		}

		// If the dropdown doesn't has a popper, initialize a new popper
		if (!dropdown._popper) {
			var placement = element.dataset.dropdownPos || 'bottom-start'
			var offset = element.dataset.dropdownOffset || '0, 5px'

			dropdown._popper = new Popper(element, dropdown, {
				placement: placement,
				modifiers: {
					offset: {
						enabled: true,
						offset: offset
					},
					preventOverflow: {
						boundariesElement: 'viewport',
					},
					flip: {
						behavior: ['right', 'left']
					}
				},
			})
		}

		return dropdown
	}

	$('body').on('keyup', '.dropdown__search input', function () {
		$('body').find('.dropdown__search input[name=' + $(this).attr('name') + ']').val($(this).val());
	});

	return {
		hideAllDropdowns: hideAllDropdowns,
		showDropdown: showDropdown,
		hideDropdown: hideDropdown,
		updateDropdownPositions: updateDropdownPositions,
		getDropdownForElement: getDropdownForElement
	}

})();
