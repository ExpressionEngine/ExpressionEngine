(function($)
{
	$.Redactor.prototype.textdirection = function()
	{
		return {
			langs: {
				en: {
					"change-text-direction": "RTL-LTR",
					"left-to-right": "Left to Right",
					"right-to-left": "Right to Left"
				}
			},
			init: function()
			{
				var that = this;
				var dropdown = {};

				dropdown.ltr = { title: that.lang.get('left-to-right'), func: that.textdirection.setLtr };
				dropdown.rtl = { title: that.lang.get('right-to-left'), func: that.textdirection.setRtl };

				var button = this.button.add('textdirection', this.lang.get('change-text-direction'));
				this.button.addDropdown(button, dropdown);
			},
			setRtl: function()
			{
				this.buffer.set();
				this.block.addAttr('dir', 'rtl');
			},
			setLtr: function()
			{
				this.buffer.set();
				this.block.removeAttr('dir');
			}
		};
	};
})(jQuery);