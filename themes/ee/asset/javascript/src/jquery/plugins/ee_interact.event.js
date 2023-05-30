/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*!
 * ExpressionEngine Custom Interact jQuery Event
 */

/* Usage Notes:
 *
 * This file adds a custom event to jquery. The interact event
 * can be thought of as a more responsive change event. On text
 * inputs and textareas, the original change event does not fire
 * until after you blur the element.
 *
 * In the past our solution has been to bind keyup on text inputs
 * and textareas, but this gets clunky quickly when trying to
 * observe events on a complex form. This custom event fills that
 * void. It can be bound on a form, a single input, or a jquery
 * object of input events. It also fires on cut and paste events,
 * which could not be supported with the keyup method.
 *
 * Usage:
 *
 * $(form).bind('interact', callback);
 * $(input).bind('interact', callback);
 *
 */
(function($) {

/* Helper method to iterate over all
 * elements inside a form
 */
function allFormElements(form, callback) {
	return $(form).map(function(){
		return this.elements ? $.makeArray(this.elements) : this;
	})
	.filter(function() {
		return this.name;	// if it doesn't have a name we can't use it for filtering
	})
	.map(callback);
}

/* Helper method to figure out if something
 * is a text input. Does not do all html5,
 * but most of what people use.
 */
function isTextInput(el) {
	if (jQuery.nodeName(el, "textarea")) {
		return true;
	}

	if ( ! jQuery.nodeName(el, "input")) {
		return false;
	}

	var type = el.type;

	if ( ! type) {
		return true;
	}

	if (type == "text" || type == "password" || type == "search" ||
		type == "url" || type == "email" || type == "tel") {
		return true;
	}

	return false;
}

/* Helper method to propagate
 * the event with a check if data changed
 * and support for delayed firing (copy, paste, etc don't update the value right away)
 */
function triggerOnText(el, data, delay) {
	delay = delay || 0;

	setTimeout(function() {
		var old_val = $.data(el, '_interact_cache'),
			new_val = el.value;

		if (old_val !== new_val) {
			$.event.trigger('interact', data, el);
			$.data(el, '_interact_cache', new_val);
		}
	}, delay);
}

var handleObjs = {};

$.event.special.interact = {

	add: function (handleObj) {
		if (handleObj.selector === undefined) {
			// for forms we need to bind on the kids instead
			if ($.nodeName(this, "form" )) {
				allFormElements(this, function() {
					$.event.special.interact.add.call(this, handleObj);
				});
				return;
			}

			// text inputs don't fire a sensible change event,
			// for live filtering we need to know when something
			// is changed as soon as the user releases the key.
			if (isTextInput(this)) {

				// store old value so we don't fire uselessly
				// this is consistent with other element change events
				$.data(this, '_interact_cache', this.value);

				// keyup
				$.event.add(this, 'keyup.specialInteract change.specialInteract', function() {
					triggerOnText(this, handleObj.data);
				});

				// cut, paste, and IE's oninput
				$.event.add(this, 'input.specialInteract cut.specialInteract paste.specialInteract', function() {
					triggerOnText(this, handleObj.data, 25);
				});

				return;
			}

			// and a change event for all other elements as well
			// as browsers that don't recognize cut and paste events
			$.event.add(this, 'change.specialInteract', function() {
				$.event.trigger('interact', handleObj.data, this);
			});
		} else {
			handleObjs[this] = handleObj;
			$.event.add(this, 'keyup.specialInteract change.specialInteract input.specialInteract cut.specialInteract paste.specialInteract', function(event) {
				if ($(this).find(handleObjs[this].selector).has(event.target)) {
					if (isTextInput(event.target)) {
						$.data(this, '_interact_cache', this.value);

						if ($.inArray(event.type, ['input', 'cut', 'paste'])) {
							triggerOnText(event.target, handleObjs[this].data, 25);
						} else {
							triggerOnText(event.target, handleObjs[this].data);
						}
					} else {
						$.event.trigger('interact', handleObjs[this].data, event.target);
					}
				}
			});
		}

	},

	remove: function (handleObj) {
		delete handleObjs[this];
		$(this).unbind('.specialInteract');
	},

};


})(jQuery);
