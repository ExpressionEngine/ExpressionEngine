RedactorX.add('plugin', 'readmore', {
    translations: {
        en: {
            "readmore": "Read more"
        }
    },

    // public
    start: function()
    {
        this.app.toolbar.add('readmore', {
            title: this.lang.get('readmore'),
            icon: '<i class="re-icon-readmore">&nbsp;</i>',
            command: 'readmore.open'
        });
    },
    open: function()
    {
        var html = '<div class="readmore"><span class="readmore__label">' + this.lang.get('readmore') + '</span></div>';
        this.app.editor.insertContent({ html: html });
    }
});
