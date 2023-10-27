RedactorX.add('plugin', 'alignment', {
    translations: {
        en: {
            "alignment": {
                "alignment": "Alignment"
            }
        }
    },
    defaults: {
        align: {
            left: 'align-left',
            center: 'align-center',
            right: 'align-right',
            justify: 'align-justify'
        }
    },
    start: function() {
        var button = {
            title: '## alignment.alignment ##',
            command: 'alignment.popup',
            position: {
                after: 'format'
            },
            blocks: {
                all: 'editable'
            }
        };

        this.app.toolbar.add('alignment', button);
    },
    popup: function(params, button) {
        var segments = {};
        var obj = this.opts.alignment.align;
        for (var key in obj) {
            if (!obj[key]) continue;
            segments[key] = { name: obj[key], prefix: 'align' };
        }

        // create
        this.app.popup.create('alignment', {
            setter: 'alignment.setAlign',
            getter: 'alignment.getAlign',
            form: {
                "align": {
                    type: 'segment',
                    label: '## alignment.alignment ##',
                    segments: segments
                }
            }
        });

        // open
        this.app.popup.open({ button: button });
    },
    getAlign: function() {
        var obj = this.opts.alignment.align;
        if (!obj) return false;

        var instance = this.app.block.get();
        var $block = instance.getBlock();
        var value = 'left';
        for (var key in obj) {
            if ($block.hasClass(obj[key])) {
                value = key;
            }
        }

        return { 'align': value };
    },
    setAlign: function(popup) {
        this.app.popup.close();

        // get data
        var data = popup.getData();
        var instance = this.app.block.get();

        instance.setClassFromObj(this.opts.alignment.align, data.align);
    }
});