RedactorX.add('plugin', 'imageresize', {
    translations: {
        en: {
            "imageresize": {
                "image-resize": "Image resize"
            }
        }
    },
    defaults: {
        minHeight: 20,
        minWidth: 100,
        zIndex: 97
    },
    subscribe: {
        'editor.blur, editor.select': function() {
            this.stop();
        },
        'image.position': function() {
            this._setResizerPosition();
        },
        'editor.unparse, editor.before.cut, editor.before.copy': function(event) {
            this._unparse(event);
        },
        'block.set': function(event) {
            this._load();
        }
    },
    stop: function() {
        this._remove();
        this._stopEvents();
    },

    // private
    _load: function() {
        var block = this.app.block.get();

        // remove resizer
        this._remove();

        if (block.isType('image')) {
            this._build(block);
        }
    },
    _build: function(block) {
        this.$block = block.getBlock();
        this.$image = block.getImage();

        // create
        this.$resizer = this.dom('<span>');
        this.$resizer.attr('id', this.prefix + '-image-resizer');
        this.$resizer.css({
            'position': 'absolute',
            'z-index': this.opts.imageresize.zIndex,
            'background-color': '#007dff',
            'width': '15px',
            'height': '15px',
            'border-radius': '8px',
            'font-size': '0',
            'border': '2px solid #fff',
            'cursor': 'move',
            'cursor': 'ew-resize'
        });

        this.$image.after(this.$resizer);
        this._setResizerPosition();
        setTimeout(this._setResizerPosition.bind(this), 30);

        this.$resizer.on('mousedown touchstart', this._press.bind(this));
    },
    _setResizerPosition: function() {
        var offsetFix = 9;
        var pos = this.$image.position();
        var width = this.$image.width();
        var height = this.$image.height();
        var resizerWidth =  this.$resizer.width();
        var resizerHeight =  this.$resizer.height();

        this.$resizer.css({
            top: Math.round(pos.top + (height/2) - resizerHeight + offsetFix) + 'px',
            left: Math.round(pos.left + width - resizerWidth + offsetFix) + 'px'
        });
    },
    _press: function(e) {
        e.preventDefault();

        var h = this.$image.height();
        var w = this.$image.width();

        this.resizeHandle = {
            x : e.pageX,
            y : e.pageY,
            el : this.$image,
            $figure: this.$image.closest('figure'),
            ratio: w / h,
            h: h,
            w: w
        };

        this.app.event.pause();
        this.app.$doc.on('mousemove.' + this.prefix + '.image-resize touchmove.' + this.prefix + '.image-resize', this._move.bind(this));
        this.app.$doc.on('mouseup.' + this.prefix + '.image-resize touchend.' + this.prefix + '.image-resize', this._release.bind(this));
        this.app.broadcast('image.resize.start', { e: e, block: this.$block, image: this.$image });
    },
    _move: function(e) {
        e.preventDefault();

        var width = this._getWidth(e);
        var height = width / this.resizeHandle.ratio;

        var $el = this.resizeHandle.el;
        var o = this.opts.imageresize;

        height = Math.round(height);
        width = Math.round(width);

        if (height < o.minHeight || width < o.minWidth) return;
        if (this._getResizableBoxWidth() <= width) return;

        if (this.resizeHandle.$figure.length !== 0 && this.resizeHandle.$figure.css('max-width') !== '') {
            this.resizeHandle.$figure.css('max-width', width + 'px');
        }

        $el.attr({ width: width, height: height });
        //$el.width(width);
        //$el.css('max-width', width + 'px');
        //$el.height(height);

        this._setResizerPosition();
        this.app.broadcast('image.resize.move', { e: e, block: this.$block, image: this.$image });
    },
    _release: function(e) {
        this._stopEvents();
        this.app.event.run();
        this.app.broadcast('image.resize.stop', { e: e, block: this.$block, image: this.$image });
    },
    _stopEvents: function() {
        this.app.$doc.off('.' + this.prefix + '.image-resize');
    },
    _remove: function() {
        this.app.editor.getLayout().find('#' + this.prefix + '-image-resizer').remove();
    },
    _getWidth: function(e) {
        var width = this.resizeHandle.w;
        if (e.targetTouches) {
            width += (e.targetTouches[0].pageX -  this.resizeHandle.x);
        }
        else {
            width += (e.pageX - this.resizeHandle.x);
        }

        return width;
    },
    _getResizableBoxWidth: function() {
        var $el = this.app.editor.getLayout();
        var width = $el.width();
        return width - parseInt($el.css('padding-left')) - parseInt($el.css('padding-right'));
    },
    _unparse: function(event) {
        var html = event.get('html');

        html = this.app.utils.wrap(html, function($w) {
            $w.find('#' + this.prefix + '-image-resizer').remove();
        }.bind(this));

        event.set('html', html);
    }
});