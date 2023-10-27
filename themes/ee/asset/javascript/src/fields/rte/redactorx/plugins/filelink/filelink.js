RedactorX.add('plugin', 'filelink', {
    translations: {
        en: {
            "filelink": {
                "file": "File",
                "upload": "Upload",
                "title": "Title",
                "choose": "Choose",
                "placeholder": "Drag to upload a file<br>or click to select"
            }
        }
    },
    defaults: {
        states: true,
        classname: false,
        upload: false,
        select: false,
        name: 'file',
        data: false,
        icon: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m14 9c.5128358 0 .9355072.38604019.9932723.88337887l.0067277.11662113v3.2c0 .9600523-.7638671 1.7205052-1.7068479 1.7941504l-.150295.0058496h-10.28571424c-.96021508 0-1.77209294-.7124503-1.85088019-1.6501425l-.00626267-.1498575v-3.2c0-.55228475.44771525-1 1-1 .51283584 0 .93550716.38604019.99327227.88337887l.00672773.11662113v3h10v-3c0-.51283584.3860402-.93550716.8833789-.99327227zm-6-8c.51283584 0 .93550716.38604019.99327227.88337887l.00672773.11662113v6h1.5c.1573787 0 .3055728.07409708.4.2.1656854.2209139.1209139.53431458-.1.7l-2.5 1.875c-.17777778.1333333-.42222222.1333333-.6 0l-2.5-1.875c-.12590292-.09442719-.2-.24262135-.2-.4 0-.27614237.22385763-.5.5-.5h1.5v-6c0-.55228475.44771525-1 1-1z"/></svg>'
    },
    subscribe: {
        'editor.load, editor.build': function() {
            this.observeStates();
        }
    },
    popups: {
        create: {
            title: '## filelink.upload ##',
            width: '400px',
            form: {
                title: { type: 'input', label: '## filelink.title ##'},
                file: {}
            }
        },
        choose: {
            collapse: false,
            title: '## filelink.choose ##',
            width: '400px'
        }
    },
    init: function() {
        this.dataStates = [];
    },
    start: function() {
        if (!this._is()) return;

        this.app.toolbar.add('file', {
            title: '## filelink.file ##',
            icon: this.opts.filelink.icon,
            command: 'filelink.popup',
            blocks: {
                all: 'editable',
                except: ['code']
            },
            position: { after: 'link' }
        });
    },
    popup: function(params, button) {
        // create
        var choosetype = 'create';
        if (this.opts.filelink.upload) {
            choosetype = 'add';
            var text = this.app.selection.getText();
            var popup = this.app.popup.create('file', this._buildPopupUpload(this.popups.create));
            popup.setData({ title: text });
        }

        // select
        if (this.opts.filelink.select) {
            var spopup = this.app.popup[choosetype]('file-select', this.popups.choose);
            this.$sbox = spopup.getBody();

            if (typeof this.opts.filelink.select === 'string') {
                this.ajax.get({
                    url: this.opts.filelink.select,
                    success: this._parseList.bind(this)
                });
            }
            else {
                this._parseList.apply(this, [this.opts.filelink.select]);
            }
        }

        // open
        this.app.popup.open({ button: button });
    },
    insertByUpload: function(response) {
        // popup close
        this.app.popup.close();

        // data
        var data = this.app.popup.getData();

        // insert
        this._insert(response, data.title);
    },
    insertBySelect: function(e) {
        e.preventDefault();

        // popup close
        this.app.popup.close();

        // selection
        var text = this.app.selection.getText();

        var $el = this.dom(e.target).closest('.' + this.prefix + '-popup-list-item');
        var data = JSON.parse(decodeURI($el.attr('data-params')));

        this._insert({ file: data }, text, true);
    },
    error: function(response) {
        this.app.broadcast('file.upload.error', { response: response });
    },
    observeStates: function() {
        this._findFiles().each(this._addFileState.bind(this));
    },
    getStates: function() {
        var $files = this._findFiles();

        // check status
        for (var key in this.dataStates) {
            var data = this.dataStates[key];
            var status = $files.is('[data-file="' + data.id + '"]');
            this._setFileState(data.id, status);
        }

        return this.dataStates;
    },

    // private
    _is: function() {
        return (this.opts.filelink.upload || this.opts.filelink.select);
    },
    _insert: function(response, title, select) {
        // loop
        for (var key in response) {
            // create file link
            var $file = this._createFileAndStore(response[key], title);

            // broadcast
            var eventType = (select) ? 'select' : 'upload';
            this.app.broadcast('file.' + eventType, { response: response, $el: $file });
            break;
        }
    },
    _buildPopupUpload: function(obj) {
        obj.form.file = {
            type: 'upload',
            upload: {
                type: 'file',
                box: true,
                placeholder: this.lang.get('filelink.placeholder'),
                url: this.opts.filelink.upload,
                name: this.opts.filelink.name,
                data: this.opts.filelink.data,
                success: 'filelink.insertByUpload',
                error: 'filelink.error'
            }
        };

        return obj;
    },
    _findFiles: function() {
        return this.app.editor.getLayout().find('[data-file]');
    },
    _addFileState: function($node) {
        var id = $node.attr('data-file');
        this.dataStates[id] = { type: 'file', status: true, url: $node.attr('src'), $el: $node, id: id };
    },
    _setFileState: function(url, status) {
        this.dataStates[url].status = status;
    },
    _parseList: function(data) {
        var $ul = this.dom('<ul>');
        $ul.addClass(this.prefix + '-popup-list');

        for (var key in data) {
            var obj = data[key];
            if (typeof obj !== 'object') continue;

            var $li = this.dom('<li>');
            var $item = this.dom('<span>');
            $li.append($item);

            // file item
            $item.addClass(this.prefix + '-popup-list-item');
            $item.attr('data-params', encodeURI(JSON.stringify(obj)));
            $item.text(obj.title || obj.name);

            // file name
            var $name = this.dom('<span>');
            $name.addClass(this.prefix + '-popup-list-aside');
            $name.text(obj.name);
            $li.append($name);

            // file size
            var $size = this.dom('<span>');
            $size.addClass(this.prefix + '-popup-list-aside');
            $size.text('(' + obj.size + ')');
            $li.append($size);

            // event
            $li.on('click', this.insertBySelect.bind(this));

            // append
            $ul.append($li);
        }

        this.$sbox.append($ul);
    },
    _createFileAndStore: function(item, title) {
        var nodes = this.app.inline.set({ tag: 'a', caret: 'after' });
        var $file = this.dom(nodes);

        $file.attr('href', item.url);
        $file.attr('data-file', (item.id) ? item.id : this.app.utils.getRandomId());
        $file.attr('data-name', item.name);

        // classname
        if (this.opts.filelink.classname) {
            $file.addClass(this.opts.filelink.classname);
        }

        // title
        title = (title && title !== '') ? title : this._truncateUrl(item.name);
        $file.html(title);

        return $file;
    },
    _truncateUrl: function(url) {
        return (url.search(/^http/) !== -1 && url.length > 20) ? url.substring(0, 20) + '...' : url;
    },
});