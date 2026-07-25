(function($R)
{
    $R.add('plugin', 'filebrowser', {
        init: function(app)
        {
            // define app
            this.app = app;

            // define services
            this.lang = app.lang;
            this.toolbar = app.toolbar;
            this.insertion = app.insertion;
        },

        // public
        start: function()
        {
            // create the button data
            var buttonData = {
                title: this.lang.get('file'),
                api: 'plugin.filebrowser.open'
            };

            // create the button
            this.$button = this.toolbar.addButton('filebrowser', buttonData);
            this.$button.setIcon('<i class="re-icon-filebrowser"></i>');
        },
        open: function()
        {
            function pickFile(that) {
                that.app.selection.save();
                return function insertFile(evt) {
                    that.app.selection.restore();
                    window.document.removeEventListener( 'filepicker:pick', insertFile );
                    const file = evt.detail;
                    if (Object.prototype.toString.call(file) === '[object String]') {
                        that.app.api('module.image.insert', file);
                    } else if (file.file_id) {
                        const file_url = EE.Rte.filedirUrls[file.upload_location_id] + file.file_name;
                        if (!file.isImage && !file.isSVG) {
                            that.app.api('module.link.insert', {text: file.path, url: file.path});
                        } else {
                            that.app.api('module.image.insert', file.path);
                        }
                    }
                }
            }
            window.document.addEventListener( 'filepicker:pick', pickFile(this) );
            window.Rte_browseImages(this.insertion, {});
        },
    });
})(Redactor);
