(function($) {

var package_settings = {
	init: function(){
		this.extension_enable_verify();
	},

	/**
	 * If extensions are disabled, show the user a confirmation, if they click
	 * OK, add a hidden input to the form so we enable extensions on submit,
	 * otherwise don't submit the form.
	 * @return void
	 */
	extension_enable_verify: function(){
		if (EE.extensions_disabled) {
			$('#mainContent form').submit(function(event) {
				if (confirm(EE.extensions_disabled_warning)) {
					// Add a hidden input to enable extensions
					$(this).append($('<input>', {"type": "hidden", "name": "enable_extensions", "value": "yes"}));
					return true;
				}

				return false;
			});
		};
	},
};

package_settings.init();

})(jQuery);
