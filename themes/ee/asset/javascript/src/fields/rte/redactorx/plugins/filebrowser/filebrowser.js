RedactorX.add('plugin', 'filebrowser', {

    // public
    start: function()
    {
        this.app.toolbar.add('filebrowser', {
            title: this.lang.get('file'),
            icon: '<i class="re-icon-filebrowser">&nbsp;</i>',
            command: 'filebrowser.open'
        });
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
                    that.app.image.insert(file);
                } else if (file.file_id) {
                    const file_url = EE.Rte.filedirUrls[file.upload_location_id] + file.file_name;
                    if (!file.isImage && !file.isSVG) {
                        var node = document.createElement('a');
                        node.setAttribute('href', file_url);
                        node.innerText = file.title;
                        that.app.insertion.insertNode(node);
                    } else {
                        that.app.image.insert({'file': {'url': file.path, 'alt': file.title}});
                    }
                }
            }
        }
        window.document.addEventListener( 'filepicker:pick', pickFile(this) );
        window.Rte_browseImages(this.app.insertion, {});
    },
});
