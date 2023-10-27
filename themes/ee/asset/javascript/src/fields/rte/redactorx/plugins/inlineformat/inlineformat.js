RedactorX.add('plugin', 'inlineformat', {
    translations: {
        en: {
            "inlineformat": {
                "inline-format": "Inline Format",
                "underline": "Underline",
                "superscript": "Superscript",
                "subscript": "Subscript",
                "mark": "Mark",
                "code": "Code",
                "shortcut": "Shortcut",
                "remove-format": "Remove Format"
            }
        }
    },
    defaults: {
        icon: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m7.39849577 1.20113038c-3.5610278 2.68125517-5.39849577 5.14152807-5.39849577 7.49360646 0 3.50902886 2.59345039 6.30526316 6 6.30526316 3.4065496 0 6-2.7962343 6-6.30526316 0-2.35207839-1.837468-4.81235129-5.39849577-7.49360646-.35616697-.26817384-.84684149-.26817384-1.20300846 0zm.60150423 2.06186962.28200813.22720401c2.50634097 2.04313256 3.71799187 3.80538426 3.71799187 5.20453283 0 2.43669386-1.73306 4.30526316-4 4.30526316-2.26694005 0-4-1.8685693-4-4.30526316 0-1.39914857 1.21165095-3.16140027 3.71799187-5.20453283z"/></svg>',
        items: ['u', 'sup', 'sub', 'mark', 'code', 'kbd'],
        itemsObj: {
            u: {
                title: '<span style="text-decoration: underline;">## inlineformat.underline ##</span>',
                params: { tag: 'u' },
                shortcut: 'Ctrl+u'
            },
            sup: {
                title: '## inlineformat.superscript ##<sup>x</sup>',
                params: { tag: 'sup' },
                shortcut: 'Ctrl+h'
            },
            sub: {
                title: '## inlineformat.subscript ##<sub>x</sub>',
                params: { tag: 'sub' },
                shortcut: 'Ctrl+l'
            },
            mark: {
                title: '<span style="background: yellow;">## inlineformat.mark ##</span>',
                params: { tag: 'mark' }
            },
            code: {
                title: '<span style="font-family: monospace; background: #f0f1f2; padding: 4px;">## inlineformat.code ##</span>',
                params: { tag: 'code' }
            },
            kbd: {
                title: '## inlineformat.shortcut ##',
                params: { tag: 'kbd' }
            }
        }
    },
    start: function() {
        this.app.toolbar.add('inlineformat', {
            title: '## inlineformat.inline-format ##',
            icon: this.opts.inlineformat.icon,
            command: 'inlineformat.popup',
            position: { after: 'deleted' },
            blocks: {
                all: 'editable'
            }
        });
    },
    popup: function(params, button) {
        var arr = this.opts.inlineformat.items;
        var inlines = this.app.selection.getNodes({ type: 'inline' });
        var items = {};
        for (var i = 0; i < arr.length; i++) {
            var key = arr[i];
            items[key] = this.opts.inlineformat.itemsObj[key];
            items[key].command = 'inline.set'
        }

        // remove format
        if (inlines.length !== 0) {
            items['remove'] = {
                title: '## inlineformat.remove-format ##',
                divider: 'top',
                command: 'inline.removeFormat'
            };
        }

        this.app.popup.create('inlineformat', {
            width: '300px',
            items: items
        });
        this.app.popup.open({ button: button });
    }
});