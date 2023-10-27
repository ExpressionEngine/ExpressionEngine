RedactorX.add('plugin', 'counter', {
    translations: {
        en: {
            "counter": {
                "words": "words",
                "chars": "chars"
            }
        }
    },
    subscribe: {
        'editor.insert, editor.set, editor.empty': function() {
            this.count();
        }
    },
    start: function() {
        this.$editor = this.app.editor.getEditor();
        this.$editor.on('keyup.' + this.prefix + '-plugin-counter paste.' + this.prefix + '-plugin-counter', this.count.bind(this));
        this.count();
    },
    stop: function() {
        this.$editor.off('.' + this.prefix + '-plugin-counter');

        this.app.statusbar.remove('words');
        this.app.statusbar.remove('chars');
    },
    count: function() {
        var words = 0, characters = 0, spaces = 0;
        var html = this.$editor.html();

        html = this._clean(html)
        if (html !== '') {
            var arrWords = html.split(/\s+/);
            var arrSpaces = html.match(/\s/g);

            words = (arrWords) ? arrWords.length : 0;
            spaces = (arrSpaces) ? arrSpaces.length : 0;

            characters = html.length;
        }

        var data = { words: words, characters: characters, spaces: spaces };

        // callback
        this.app.broadcast('counter', data);

        // statusbar
        this.app.statusbar.add('words', this.lang.get('counter.words') + ': ' + data.words);
        this.app.statusbar.add('chars', this.lang.get('counter.chars') + ': ' + data.characters);
    },

    // private
    _clean: function(html) {
        html = html.replace(/<\/(.*?)>/gi, ' ');
        html = html.replace(/<(.*?)>/gi, '');
        html = html.replace(/\t/gi, '');
        html = html.replace(/\n/gi, ' ');
        html = html.replace(/\r/gi, ' ');
        html = html.replace(/&nbsp;/g, '1');
        html = html.trim();
        html = this.app.utils.removeInvisibleChars(html);

        return html;
    }
});