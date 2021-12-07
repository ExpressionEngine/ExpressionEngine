(function($) {
	$('input.color-picker').each(function() {
		var input = this;
		var inputName = input.name;
		var inputValue = input.value;

		$(input).wrap('<div>');

		var newContainer = $(input).parent();

        ReactDOM.render(React.createElement(ColorPicker, {
            inputName: inputName,
            initialColor: inputValue,
            allowedColors: 'any',
            swatches: ['FA5252', 'FD7E14', 'FCC419', '40C057', '228BE6', 'BE4BDB', 'F783AC'],

            onChange: function(newColor) {
                // Change colors
                input.value = newColor;
            }
        }, null), newContainer[0]);
    });
})(jQuery);