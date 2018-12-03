/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
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
		var $status_tag = $('.status-tag', modal);

		// Change the status example's name when you change the name
		$('input[name="status"]', modal).on('keyup', function(event) {
			var status = $(this).val() ? $(this).val() : EE.status.default_name;
			$status_tag.text(status);
		});

        $('div.colorpicker-init').each(function() {
            var container = this;

            // TMP css
            // TODO: REMOVE ME
            $(tmpCss()).insertBefore(container)

            ReactDOM.render(React.createElement(ColorPicker, {
                inputName: 'highlight',
                initialColor: this.dataset.color,
                mode: 'both',
                swatches: ['E34834', 'F8BD00', '1DC969', '2B92D8', 'DE32E0', 'fff', '000'],

                onChange: function(newColor) {
                    // Change background and border colors
                    $status_tag.css('background-color', newColor).css('border-color', newColor);

                    // Set foreground color
                    var foregroundColor = new SimpleColor(newColor).fullContrastColor().hexStr;
                    $status_tag.css('color', foregroundColor);
                },
                componentDidMount: function () {
                    EE.cp.formValidation.bindInputs(container);
                }
            }, null), container);
        });
	}
}

new MutableSelectField('statuses', Object.assign(EE.channelManager.statuses, options))

})(jQuery);
