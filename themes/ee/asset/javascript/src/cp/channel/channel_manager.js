/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

(function($) {

new MutableSelectField('field_groups', EE.channelManager.fieldGroup)

var options = {
	onFormLoad: function(modal) {
		FieldManager.fireEvent('fieldModalDisplay', modal)

		EE.cp.fieldToggleDisable(modal)

		$('input[name=field_label]', modal).bind("keyup keydown", function() {
			$(this).ee_url_title('input[name=field_name]', true);
		});
	}
}

new MutableSelectField('custom_fields', Object.assign(EE.channelManager.fields, options))

new MutableSelectField('cat_group', EE.channelManager.catGroup)

var options = {
	onFormLoad: function(modal) {
		var status_tag = modal[0].querySelector('.status-tag');

		// Change the status example's name when you change the name
		$('input[name="status"]', modal).on('keyup', function(event) {
			var status = this.value || EE.status.default_name;
			status_tag.innerText = status;
		});

        $('input.color-picker', modal).each(function() {
			var input = this;
			var inputName = input.name;
			var inputValue = input.value;

			// Replace the input with a container to hold the color picker component
			var newContainer = document.createElement('div');
			input.parentNode.replaceChild(newContainer, input);

            ReactDOM.render(React.createElement(ColorPicker, {
                inputName: inputName,
                initialColor: inputValue,
                allowedColors: 'any',
                swatches: ['FA5252', 'FD7E14', 'FCC419', '40C057', '228BE6', 'BE4BDB', 'F783AC'],

                onChange: function(newColor) {
                    // Change colors
                    status_tag.style.color = newColor;
                    status_tag.style.borderColor = newColor;
                }
            }, null), newContainer);
        });
	}
}

new MutableSelectField('statuses', Object.assign(EE.channelManager.statuses, options))

})(jQuery);
