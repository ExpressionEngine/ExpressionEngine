RedactorX.add('plugin', 'clips', {
    translations: {
        en: {
            "clips": {
                "clips": "Clips"
            }
        }
    },
    defaults: {
        icon: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m12.1666667 1c1.0193924 0 1.8333333.83777495 1.8333333 1.85714286v10.28571424c0 1.0193679-.8139409 1.8571429-1.8333333 1.8571429h-8.33333337c-1.01868744 0-1.83333333-.8379215-1.83333333-1.8571429v-10.28571424c0-1.01922137.81464589-1.85714286 1.83333333-1.85714286zm-.1666667 2h-8v10h8zm-2 7c.5522847 0 1 .4477153 1 1 0 .5128358-.3860402.9355072-.8833789.9932723l-.1166211.0067277h-4c-.55228475 0-1-.4477153-1-1 0-.5128358.38604019-.9355072.88337887-.9932723l.11662113-.0067277zm0-3c.5522847 0 1 .44771525 1 1 0 .51283584-.3860402.93550716-.8833789.99327227l-.1166211.00672773h-4c-.55228475 0-1-.44771525-1-1 0-.51283584.38604019-.93550716.88337887-.99327227l.11662113-.00672773zm0-3c.5522847 0 1 .44771525 1 1 0 .51283584-.3860402.93550716-.8833789.99327227l-.1166211.00672773h-4c-.55228475 0-1-.44771525-1-1 0-.51283584.38604019-.93550716.88337887-.99327227l.11662113-.00672773z"/></svg>',
        items: false
    },
    start: function() {
        if (!this.opts.clips.items) return;

        this.app.toolbar.add('clips', {
            title: '## clips.clips ##',
            icon: this.opts.clips.icon,
            command: 'clips.popup',
            blocks: {
                all: 'editable'
            }
        });
    },
    popup: function(params, button) {
        var items = {};
        for (var key in this.opts.clips.items) {

            items[key] = {
                title: this.opts.clips.items[key].title,
                command: 'clips.insert'
            }
        }

        this.app.popup.create('clips', { items: items });
        this.app.popup.open({ button: button });
    },
    insert: function(params, item, name) {
        this.app.popup.close();

        var html = this.opts.clips.items[name].html;
        this.app.editor.insertContent({ html: html });
    }
});