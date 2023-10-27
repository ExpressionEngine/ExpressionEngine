RedactorX.add('plugin', 'definedlinks', {
    defaults: {
        items: false
    },
    subscribe: {
        'popup.open': function() {
            if (this.opts.definedlinks.items === false) return;

            var name = this.app.popup.getName();
            if (name === 'link') {
                this._build();
            }
        }
    },

    // private
    _build: function() {
        var $item = this.app.popup.getFormItem('text');
        var $box = this.dom('<div>').addClass(this.prefix + '-form-item');

        // select
        this.$select = this._create();

        $box.append(this.$select);
        $item.before($box);
    },
    _change: function(e) {
        var key = this.dom(e.target).val();
        var data = this.opts.definedlinks.items[key];
        var $text = this.app.popup.getInput('text');
        var $url = this.app.popup.getInput('url');
        var name = data.name;
        var url = data.url;

        if (data.url === false) {
            url = '';
            name = '';
        }

        // text
        if ($text.val() === '') {
            $text.val(name);
        }

        // url
        $url.val(url);
    },
    _create: function() {
        var items = this.opts.definedlinks.items;
        var $select = this.dom('<select>').addClass(this.prefix + '-form-select');
        $select.on('change', this._change.bind(this));

        for (var i = 0; i < items.length; i++) {
            var data = items[i];
            var $option = this.dom('<option>');
            $option.val(i);
            $option.html(data.name);

            $select.append($option);
        }

        return $select;
    }
});