(function($R)
{
    $R.add('plugin', 'rte_definedlinks', {
        translations: {
            en: {
                "pages": "Pages"
            }
        },
        init: function(app)
        {
            this.app = app;
            this.opts = app.opts;

            this.component = app.component;

            // local
            this.links = [];
        },
        // messages
        onmodal: {
            link: {
                open: function($modal, $form)
                {
                    if (!this.opts.definedlinks) return;

                    this.$modal = $modal;
                    this.$form = $form;

                    this._load();
                }
            }
		},

		// private
		_load: function()
		{
    		if (typeof this.opts.definedlinks === 'object')
    		{
                this._build(this.opts.definedlinks);
    		}
            else
            {
        		$R.ajax.get({
            		url: this.opts.definedlinks,
            		success: this._build.bind(this)
        		});
    		}
		},
		_build: function(data)
		{
            var $selector = this.$modal.find('#redactor-defined-links');
            if ($selector.length === 0)
            {
                var $body = this.$modal.getBody();
                var $item = $R.dom('<div class="form-item" />');
                var $label = $R.dom('<label for="redactor-defined-links">' + $R.lang[$R.opts.lang]['pages'] + '</label>');
                var $selector = $R.dom('<select id="redactor-defined-links" />');

                $item.append($label);
                $item.append($selector);
                $body.prepend($item);
            }

            this.links = [];

            $selector.html('');
            $selector.off('change');

            $selector.append($R.dom('<option>'));

            for (var key in data)
            {
                if (!data.hasOwnProperty(key) || typeof data[key] !== 'object')
                {
                    continue;
                }

                this.links[key] = data[key];

                var $option = $R.dom('<option>');
                $option.val(key);
                $option.html(data[key].text);

                $selector.append($option);
            }

            $selector.on('change', this._select.bind(this));
		},
		_select: function(e)
		{
			var formData = this.$form.getData();
			var key = $R.dom(e.target).val();
			var data = { text: '', url: '' };

			if (key !== '')
			{
				data.text = this.links[key].text;
				data.url = this.links[key].href;
			}

			if (formData.text !== '')
			{
    			data = { url: data.url };
			}

			this.$form.setData(data);
		}
    });
})(Redactor);
