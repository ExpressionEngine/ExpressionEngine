RedactorX.add('plugin', 'beyondgrammar', {
    subscribe: {
        'editor.docmousedown': function(event) {
            var e = event.get('e');
            if (e && this._isPwa(e)) {
                e.preventDefault();
            }
        },
        'editor.before.blur': function(event) {
            var e = event.get('e');
            if (e && this._isPwa(e)) {
                var offset = this.app.offset.get();
                setTimeout(function() {
                    this.app.offset.set(offset);
                }.bind(this), 10);
                event.stop();
            }
        },
        'editor.unparse': function(event) {
            var html = event.get('html');
            html = this.app.utils.wrap(html, function($w) {
                $w.find('.pwa-mark').unwrap();
            });

            event.set('html', html);
        }
    },
    start: function() {
        this.GrammarChecker = this._getGrammarChecker();
        if (!this.opts.beyondgrammar || !this.GrammarChecker) return;
        this._activate();
    },

    // private
    _isPwa: function(e) {
        return (this.dom(e.target).hasClass('pwa-suggest'));
    },
    _activate: function() {
        this.$editor = this.app.editor.getEditor();
        this.$editor.attr('spellcheck', false);
        var checker = new this.GrammarChecker(this.$editor.get(), this.opts.beyondgrammar.service, this.opts.beyondgrammar.grammar);
        checker.init().then(function() {
            //grammar checker is inited and can be activate
            checker.activate();
        });
    },
    _getGrammarChecker: function() {
        return (typeof window["BeyondGrammar"] === 'undefined') ? false : window["BeyondGrammar"]["GrammarChecker"];
    }
});