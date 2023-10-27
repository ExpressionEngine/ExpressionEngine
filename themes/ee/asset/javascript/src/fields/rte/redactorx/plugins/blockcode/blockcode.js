RedactorX.add('plugin', 'blockcode', {
    translations: {
        en: {
            "blockcode": {
                "save": "Save",
                "cancel": "Cancel",
                "edit-code": "Edit Code"
            }
        }
    },
    defaults: {
        icon: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m10.6128994 3.20970461.0942074.08318861 4 4c.3604839.36048396.3882135.92771502.0831886 1.32000622l-.0831886.09420734-4 4.00000002c-.3905243.3905243-1.02368929.3905243-1.41421358 0-.36048396-.360484-.3882135-.927715-.08318861-1.3200062l.08318861-.0942074 3.29210678-3.2928932-3.29210678-3.29289322c-.36048396-.36048396-.3882135-.92771502-.08318861-1.32000622l.08318861-.09420734c.36048396-.36048396.92771498-.3882135 1.32000618-.08318861zm-3.90579262.08318861c.36048396.36048396.3882135.92771502.08318861 1.32000622l-.08318861.09420734-3.29210678 3.29289322 3.29210678 3.2928932c.36048396.360484.3882135.927715.08318861 1.3200062l-.08318861.0942074c-.36048396.3604839-.92771502.3882135-1.32000622.0831886l-.09420734-.0831886-4-4.00000002c-.36048396-.36048396-.3882135-.92771502-.08318861-1.32000622l.08318861-.09420734 4-4c.39052429-.39052429 1.02368927-.39052429 1.41421356 0z"/></svg>',
        popup: {
            width: '100%',
            title: '## blockcode.edit-code ##',
            form: {
                'code': { type: 'textarea', rows: '8' }
            },
            footer: {
                save: { title: '## blockcode.save ##', command: 'blockcode.save', type: 'primary' },
                cancel: { title: '## blockcode.cancel ##', close: true }
            }
        }
    },
    init: function() {
        this.offset = false;
    },
    start: function() {
        this.app.control.add('blockcode', {
            icon: this.opts.blockcode.icon,
            command: 'blockcode.edit',
            title: '## blockcode.edit-code ##',
            position: {
                before: 'duplicate'
            }
        });
    },
    edit: function() {
        var current = this._getCurrent();
        var code = current.getOuterHtml();
        code = this.app.parser.unparse(code);

        if (current.isEditable()) {
            this.offset = this.app.offset.get(current.getBlock());
        }

        // create
        this.app.popup.create('code', this.opts.blockcode.popup);
        this.app.popup.setData({ code: code });

        // open
        this.app.popup.open({ focus: 'code' });

        // hadnle & codemirror
        this._buildInputHandle();
        this._buildCodemirror();
    },
    save: function() {
        this.app.popup.close();

        var current = this._getCurrent();
        var code = this._getCode();
        if (code === '') {
            return;
        }

        // create
        code = this.app.parser.parse(code, false);
        var $source = this.dom(code);
        var instance = this.app.create('block.' + current.getType(), $source);

        // change
        current.change(instance);

        // set editable focus
        if (this.offset && instance.isEditable()) {
            this.app.offset.set(instance.getBlock(), this.offset);
        }

        // clear offset
        this.offset = false;
    },

    // private
    _getCurrent: function() {
        var current = this.app.block.get();
        if (current.isSecondLevel()) {
            current = current.getFirstLevel();
        }

        return current;
    },
    _getCode: function() {
        var data = this.app.popup.getData();
        var code = data.code.trim();
        code = this.app.codemirror.val(code);

        return code;
    },
    _buildInputHandle: function() {
        var $input = this.app.popup.getInput('code');
        $input.on('keydown', this.app.input.handleTextareaTab.bind(this));
    },
    _buildCodemirror: function() {
        var $input = this.app.popup.getInput('code');

        this.app.codemirror.create({ el: $input, height: '200px', focus: true });
        this.app.popup.updatePosition();
    }
});