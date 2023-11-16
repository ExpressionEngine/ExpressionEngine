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
            icon: '<i class="re-icon-readmore"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="18" height="18"><path d="M3.598.687h1.5v5h-1.5zm14.5 0h1.5v5h-1.5z"></path><path d="M19.598 4.187v1.5h-16v-1.5zm-16 14.569h1.5v-5h-1.5zm14.5 0h1.5v-5h-1.5z"></path><path d="M19.598 15.256v-1.5h-16v1.5zM5.081 9h6v2h-6zm8 0h6v2h-6zm-9.483 1L0 12.5v-5z"></path></svg></i>',
            command: 'readmore.open'
        });
    },
    open: function()
    {
        var html = '<div class="readmore"><span class="readmore__label">' + this.lang.get('readmore') + '</span></div>';
        this.app.editor.insertContent({ html: html });
    }
});
