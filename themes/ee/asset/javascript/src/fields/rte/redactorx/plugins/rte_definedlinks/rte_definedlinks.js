RedactorX.add('plugin', 'rte_definedlinks', {
    defaults: {
        items: false
    },
    init: function() {
        this.items = [];
    },
    subscribe: {
        'popup.open': function() {
            var name = this.app.popup.getName();
            if (name === 'link') {
                if (this.items.length === 0) {
                    this._load();
                }
                if (this.items.length === 0) return;
                this._build();
            }
        }
    },

    // private
    _load: function() {
        this.ajax.get({
            url: this.opts.definedlinks,
            success: function(response) {
                this.items = response
                this._build()
            }.bind(this)
        });
    },
    _build: function() {
        var $item = this.app.popup.getFormItem('text');
        var $box = this.dom('<div>').addClass(this.prefix + '-form-item');

        // select
        this.$select = this._create();

        $box.append(this.$select);
        $item.before($box);
        Dropdown.renderFields();
    },
    // _change: function(e) {
    //     var url = this.dom(e.target).val();
    //     var name = this.dom(e.target).text();
    //     var $text = this.app.popup.getInput('text');
    //     var $url = this.app.popup.getInput('url');

    //     // text
    //     if ($text.val() === '') {
    //         $text.val(name);
    //     }

    //     // url
    //     $url.val(url);
    // },
    _create: function() {
        var $div = this.dom('<div>').addClass(this.prefix + '-form-div');
        var $choices = {};

        $.each(this.items, function(item, val) {
            $choices[item] = val;
        });

        var options = {
            name: this.prefix + '-form-dropdown-react',
            items: $choices,
            initialItems: $choices,
            disabled: false,
            tooMany: 8,
            limit: 100,
            emptyText: "Select a Page",
            conditionalRule: "rx-redactor-dropdown",
        };

        var dropdownReactAttr = btoa(JSON.stringify(options));
        $div.attr('data-input-value', this.prefix + '-form-dropdown-react');
        $div.attr('data-dropdown-react', dropdownReactAttr);

        return $div
    }
    // _create: function() {
    //     var $select = this.dom('<select>').addClass(this.prefix + '-form-select');
    //     $select.on('change', this._change.bind(this));

    //     $select.append(this.dom('<option>'));
        
    //     for (var i = 0; i < this.items.length; i++) {
    //         var data = this.items[i];
    //         var $option = this.dom('<option>');
    //         $option.val(data.href);
    //         $option.html(data.text);

    //         $select.append($option);
    //     }

    //     return $select;
    // }
});