RedactorX.add('plugin', 'handle', {
    translations: {
        en: {
            "handle": {
                "handle": "Handle"
            }
        }
    },
    defaults: {
        url: false,
        start: 1,
        trigger: '@'
    },
    subscribe: {
        'editor.keydown': function(event) {
            this._listen(event);
        },
        'editor.keyup': function(event) {
            if (this.opts.handle.url === false) return;
            this._handle(event);
        }
    },
    start: function() {
        this.handleLen = this.opts.handle.start;
    },
    stop: function() {
        this._hide();
    },

    // private
    _handle: function(event) {
        var e = event.get('e');
        var key = e.which;
        var ctrl = e.ctrlKey || e.metaKey;
        var arrows = [37, 38, 39, 40];
        var ks = this.app.keycodes;

        if (key === ks.ESC) {
            this.app.selection.restore();
            return;
        }
        if (key === ks.DELETE || key === ks.SPACE || key === ks.SHIFT || ctrl || (arrows.indexOf(key) !== -1)) {
            return;
        }

        if (key === ks.BACKSPACE) {
            this.handleLen = this.handleLen - 2;
            if (this.handleLen <= this.opts.handle.start) {
                this._hide();
            }
        }

        this._emit();
    },
    _listen: function(event) {
        var e = event.get('e');
        var key = e.which;
        var ks = this.app.keycodes;

        // listen enter
        if (this._isShown() && key === ks.ENTER) {
            var $item = this._getActiveItem();
            if ($item.length === 0) {
                this._hideForce();
                return;
            }
            else {
                e.preventDefault();
                event.stop();
                this._replace(e, $item);
                return;
            }
        }

        // listen down / up
        if (this._isShown() && (key === 40 || key === 38)) {
            e.preventDefault();
            event.stop();

            var $item = this._getActiveItem();
            if ($item.length === 0) {
                var $first = this._getFirstItem();
                this._setActive($first);
            }
            // down
            else if (key === 40) {
                this._setNextActive($item);
            }
            // up
            else if (key === 38) {
                this._setPrevActive($item);
            }
        }
    },
    _getItems: function() {
        return this.$panel.find('.' + this.prefix + '-panel-item');
    },
    _getActiveItem: function() {
        return this._getItems().filter(function($node) {
            return $node.hasClass('active');
        });
    },
    _getFirstItem: function() {
        return this._getItems().first();
    },
    _getLastItem: function() {
        return this._getItems().last();
    },
    _setActive: function($el) {
        this._getItems().removeClass('active');
        $el.addClass('active');

        var itemHeight = $el.outerHeight();
        var itemTop = $el.position().top;
        var itemsScrollTop = this.$panel.scrollTop();
        var scrollTop = itemTop + itemHeight * 2;
        var itemsHeight = this.$panel.outerHeight();

        this.$panel.scrollTop(
            scrollTop > itemsScrollTop + itemsHeight ? scrollTop - itemsHeight :
                itemTop - itemHeight < itemsScrollTop ? itemTop - itemHeight :
                itemsScrollTop
        );
    },
    _setNextActive: function($el) {
        var $next = $el.next();
        if ($next.length !== 0) {
            this._setActive($next);
        }
        else {
            var $first = this._getFirstItem();
            this._setActive($first);
        }
    },
    _setPrevActive: function($el) {
        var $prev = $el.prev();
        if ($prev.length !== 0) {
            this._setActive($prev);
        }
        else {
            var $last = this._getLastItem();
            this._setActive($last);
        }
    },
    _emit: function() {
        var re = new RegExp('^' + this.opts.handle.trigger);
        this.handleStr = this.app.selection.getText('before', this.handleLen);
        this.handleStr2 = this.app.selection.getText('before', this.handleLen+1);

        // detect
        if (re.test(this.handleStr)) {
            if (this.handleStr2 && this.handleStr2[0] === ' ' || this.handleStr2[0] === '') {
                this.handleStr = this.handleStr.replace(this.opts.handle.trigger, '');
                this.handleLen++;

                if ((this.handleLen-1) > this.opts.handle.start) {
                    this._load();
                }
            }
        }
    },
    _isShown: function() {
        return (this.$panel && this.$panel.hasClass('open'));
    },
    _load: function() {
        this.ajax.post({
            url: this.opts.handle.url,
            data: 'handle=' + this.handleStr,
            success: this._parse.bind(this)
        });
    },
    _parse: function(json) {
        if (json === '' || (Array.isArray(json) && json.length === 0)) {
            if (this.$panel) this.$panel.remove();
            return;
        }
        var data = (typeof json === 'object') ? json : JSON.parse(json);

        this._build(data);
    },
    _build: function(data) {

        this.data = data;
        this.$panel = this.app.$body.find('.' + this.prefix + '-panel');

        if (this.$panel.length === 0) {
            this.$panel = this.dom('<div>').addClass(this.prefix + '-panel');
            this.app.$body.append(this.$panel);
        }
        else {
            this.$panel.html('');
        }

        // events
        this._stopEvents();
        this._startEvents();

        // data
        for (var key in data) {
            var $item = this.dom('<div>').addClass(this.prefix + '-panel-item');
            var $trigger = this.dom('<a>').attr('href', '#');
            $trigger.html(data[key].item);
            $trigger.attr('data-key', key);
            $trigger.on('click', this._replace.bind(this));

            $item.append($trigger);
            this.$panel.append($item);
        }

        // position
        var scrollTop = this.app.$doc.scrollTop();
        var pos = this.app.selection.getPosition();

        this.$panel.addClass('open');
        this.$panel.css({
            top: (pos.bottom + scrollTop) + 'px',
            left: pos.left + 'px'
        });

        this.app.selection.save();
    },
    _replace: function(e, $el) {
        e.preventDefault();
        e.stopPropagation();

        var $item;
        if ($el) {
            $item = $el.find('a');
        }
        else {
            $item = this.dom(e.target);
        }
        var key = $item.attr('data-key');
        var replacement = this.data[key].replacement;

        this.app.marker.insert('start');
        var marker = this.app.marker.find('start');
        if (marker === false) return;

        var $marker = this.dom(marker);
        var current = marker.previousSibling;

        var currentText = current.textContent;
        var re = new RegExp(this.opts.handle.trigger + this.handleStr + '$');

        currentText = currentText.replace(re, '');
        current.textContent = currentText;

        $marker.before(replacement);
        this.app.selection.restoreMarker();

        this._hide();
    },
    _reset: function() {
        this.handleStr = false;
        this.handleLen = this.opts.handle.start;
        this.$panel = false;
    },
    _hide: function(e) {
        var hidable = false;
        var key = (e && e.which);
        var ks = this.app.keycodes;

        if (!e) {
            hidable = true;
        }
        else if (e.type === 'click' || key === ks.ESC || key === ks.SPACE) {
            hidable = true;
        }

        if (hidable) {
            this._hideForce();
        }
    },
    _hideForce: function() {
        if (this.$panel) this.$panel.remove();
        this._reset();
        this._stopEvents();
    },
    _startEvents: function() {
        var name = 'click.' + this.prefix + '-plugin-handle keydown.' + this.prefix + '-plugin-handle';

        this.app.$doc.on(name, this._hide.bind(this));
        this.app.editor.getEditor().on(name, this._hide.bind(this));
    },
    _stopEvents: function() {
        var name = '.' + this.prefix + '-plugin-handle';

        this.app.$doc.off(name);
        this.app.editor.getEditor().off(name);
    }
});