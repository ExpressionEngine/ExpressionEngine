RedactorX.add('plugin', 'textexpander', {
    defaults: {
        items: false
    },
    start: function() {
        if (!this.opts.textexpander.items) return;

        this.$editor = this.app.editor.getEditor();
        this.$editor.on('keyup.' + this.prefix + '-plugin-textexpander', this._expand.bind(this));
    },
    stop: function() {
        this.$editor.off('.' + this.prefix + '-plugin-textexpander');
    },

    // private
    _expand: function(e) {
        var key = e.which;
        if (key !== this.app.keycodes.SPACE) {
            return;
        }

        var items = this.opts.textexpander.items;
        for (var key in items) {
            var str = items[key];
            var re = new RegExp(this.app.utils.escapeRegExp(key) + '\\s$');
            var len = key.length + 1;
            var rangeText = this.app.selection.getText('before', len).trim();

            if (key === rangeText) {
                return this._replaceSelection(re, str);
            }

        }
    },
    _replaceSelection: function(re, replacement) {
        this.app.marker.insert();

        var marker = this.app.marker.find('start');
        var current = marker.previousSibling;
        var currentText = current.textContent;

        currentText = currentText.replace(/&nbsp;/, ' ');
        currentText = currentText.replace(re, replacement);
        current.textContent = currentText;

        this.app.marker.remove();
        return;
    }
});