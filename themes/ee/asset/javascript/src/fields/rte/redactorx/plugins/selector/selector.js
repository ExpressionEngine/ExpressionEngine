RedactorX.add('plugin', 'selector', {
    translations: {
        en: {
            "selector": {
                "class-id": "Class & ID"
            }
        }
    },
    defaults: {
        icon: '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="m10.6663083 2.14369002c.5071647.07664087.8669828.53941479.8491564 1.06286535l-.0109223.12172524-.2435424 1.67154209 1.239.0001773c.5522847 0 1 .44771525 1 1 0 .51283584-.3860402.93550716-.8833789.99327227l-.1166211.00672773-1.531-.0001773-.291 2 1.322.0001773c.5522847 0 1 .44771525 1 1 0 .5128358-.3860402.9355072-.8833789.9932723l-.1166211.0067277-1.613-.0001773-.3121956 2.1487091c-.08323349.571695-.59347185.9682364-1.13964926.8857001-.50716474-.0766409-.86698283-.5394148-.84915642-1.0628653l.01092235-.1217253.26807893-1.8498186h-1.978l-.31219557 2.1487091c-.08323352.571695-.59347188.9682364-1.13964929.8857001-.50716474-.0766409-.86698283-.5394148-.84915642-1.0628653l.01092235-.1217253.26807893-1.8498186-1.365.0001773c-.55228475 0-1-.4477153-1-1 0-.51283584.38604019-.93550716.88337887-.99327227l.11662113-.00672773 1.656-.0001773.292-2-1.448.0001773c-.55228475 0-1-.44771525-1-1 0-.51283584.38604019-.93550716.88337887-.99327227l.11662113-.00672773 1.739-.0001773.28765901-1.97043255c.08323352-.571695.59347189-.96823646 1.1396493-.88570013.50716473.07664087.86698282.53941479.84915642 1.06286535l-.01092236.12172524-.24354237 1.67154209h1.978l.28765901-1.97043255c.08323352-.571695.59347189-.96823646 1.13964929-.88570013zm-2.0103083 6.85613268.292-2h-1.979l-.291 2z"/></svg>'
    },
    popups: {
        base: {
            title: '## selector.class-id ##',
            width: '100%',
            form: {
                classname: { type: 'input', label: 'Class' },
                id: { type: 'input', label: 'ID' }
            },
            footer: {
                insert: { title: '## buttons.save ##', command: 'selector.save', type: 'primary' },
                cancel: { title: '## buttons.cancel ##', command: 'popup.close' }
            }
        }
    },
    init: function() {
        this.editorClasses = [];
    },
    start: function() {
        var button = {
            title: '## selector.class-id ##',
            icon: this.opts.selector.icon,
            command: 'selector.popup',
            blocks: {
                all: 'all'
            }
        };


        this.app.toolbar.add('selector', button);

        button.position = { 'before': 'duplicate' };
        this.app.control.add('selector', button);
    },
    popup: function(params, button) {

        // create
        this.app.popup.create('selector',  this.popups.base);

        var instance = this.app.block.get();
        var $block = instance.getBlock();
        var classname = $block.attr('class');
        var id = $block.attr('id');

        // classes
        var classes = classname.split(' ');
        var classname = '';
        for (var i = 0; i < classes.length; i++) {
            if (classes[i].search(this.prefix + '-') === -1) {
                classname += classes[i] + ' ';
            }
            else {
                this.editorClasses.push(classes[i]);
            }
        }

        // data
        this.app.popup.setData({
            id: id,
            classname: classname.trim()
        });

        // open
        this.app.popup.open({ button: button, focus: 'classname' });
    },
    save: function() {
        this.app.popup.close();

        var instance = this.app.block.get();
        var $block = instance.getBlock();
        var data = this.app.popup.getData();

        // set id
        if (data.id === '') {
            $block.removeAttr('id');
        }
        else {
            $block.attr('id', data.id);
        }

        // set class
        if (this.editorClasses.length > 0) {
            data.classname = data.classname + ' ' + this.editorClasses.join(' ');
        }

        $block.attr('class', data.classname);
        this.editorClasses = [];
    }
});