/**
	 * If extensions are disabled, show the user a confirmation, if they click
	 * OK, add a hidden input to the form so we enable extensions on submit,
	 * otherwise don't submit the form.
	 * @return void
	 */

(function(a){({init:function(){this.extension_enable_verify()},extension_enable_verify:function(){EE.extensions_disabled&&a("#mainContent form").submit(function(b){return confirm(EE.extensions_disabled_warning)?(a(this).append(a("<input>",{type:"hidden",name:"enable_extensions",value:"yes"})),!0):!1})}}).init()})(jQuery);
