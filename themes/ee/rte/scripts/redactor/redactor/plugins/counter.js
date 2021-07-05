(function($)
{
	$.Redactor.prototype.counter = function()
	{
		return {
			init: function()
			{
				if (typeof this.opts.callbacks.counter === 'undefined')
				{
					return;
				}



				this.core.editor().on('keyup.redactor-plugin-counter', $.proxy(this.counter.count, this));
			},
			count: function()
			{
				var words = 0, characters = 0, spaces = 0;
				var html = this.code.get();

				var text = html.replace(/<\/(.*?)>/gi, ' ');
				text = text.replace(/<(.*?)>/gi, '');
				text = text.replace(/\t/gi, '');
				text = text.replace(/\n/gi, ' ');
				text = text.replace(/\r/gi, ' ');
				text = text.replace(/\u200B/g, '');
				text = $.trim(text);

				if (text !== '')
				{
					var arrWords = text.split(/\s+/);
					var arrSpaces = text.match(/\s/g);

					words = (arrWords) ? arrWords.length : 0;
					spaces = (arrSpaces) ? arrSpaces.length : 0;

					characters = text.length;

				}

				this.core.callback('counter', { words: words, characters: characters, spaces: spaces });

			}
		};
	};
})(jQuery);