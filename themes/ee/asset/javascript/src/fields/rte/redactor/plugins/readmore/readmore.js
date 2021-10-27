(function($R)
{
    $R.add('plugin', 'readmore', {
        translations: {
            en: {
                "readmore": "Read more"
            }
        },
        init: function(app)
        {
            this.app = app;
            this.lang = app.lang;
            this.toolbar = app.toolbar;
            this.component = app.component;
            this.insertion = app.insertion;
            this.inspector = app.inspector;
        },
        // messages

        oncontextbar: function(e, contextbar)
        {
            var data = this.inspector.parse(e.target)
            if (typeof(data.$el) !== 'undefined' && data.$el.find('div.readmore').length == 1)
            {
                var node = data.getComponent();
                if (typeof(node) !== 'undefined' && node !== false) {
                    var buttons = {
                        "remove": {
                            title: this.lang.get('delete'),
                            api: 'plugin.widget.remove',
                            args: node
                        }
                    };

                    contextbar.set(e, node, buttons, 'bottom');
                }
            }
        },

        // public
        start: function()
        {
            var obj = {
                title: this.lang.get('readmore'),
                api: 'plugin.readmore.open'
            };

            var $button = this.toolbar.addButton('readmore', obj);
            $button.setIcon('<i class="re-icon-readmore"></i>');
        },
        open: function()
		{
            this._insert();
		},
        remove: function(node)
        {
            this.component.remove(node);
        },

        // private
		_insert: function(data)
		{

    		var html = '<div class="readmore"><span class="readmore__label">' + this.lang.get('readmore') + '</span></div>';
            var $component = this.component.create('widget', html);
            $component.attr('data-widget-code', encodeURI(html));
    		this.insertion.insertHtml($component);
		}
    });
})(Redactor);
