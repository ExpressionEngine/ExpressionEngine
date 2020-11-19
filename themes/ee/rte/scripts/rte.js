window.Rte;

(function($) {

    /**
     * Rte
     */
    Rte = function(id, config, defer) {
        // Allow initializing by a jQuery object that matched something
        if (typeof id == "object" && typeof id.is == "function" && id.is('textarea')) {
            this.$element = id;
        } else {
            this.$element = $('#' + id);
        }

        // No luck
        if (this.$element.length == 0) {
            return;
        }

        this.id = id;

        if (typeof config == "undefined") {
            config = this.$element.data('config');
        }

        this.config = (EE.Rte.configs[config] || EE.Rte.configs['default']);

        if (typeof defer == "undefined") {
            this.defer = this.$element.data('defer') == "y";
        } else {
            this.defer = defer;
        }

        if (this.defer) {
            this.showIframe();
        } else {
            this.initCKEditor();
        }
    };

    Rte.prototype = {
        /**
         * Show Iframe
         */
        showIframe: function() {
            var width = (this.config.width ? this.config.width.toString() : '100%'),
                height = (this.config.height ? this.config.height.toString() : '200'),
                $textarea = this.$element.hide();

            if (width.match(/\d$/)) width += 'px';
            if (height.match(/\d$/)) height += 'px';

            this.$iframe = $('<iframe class="rte" style="width:'+width+'; height:'+height+';" frameborder="0" />').insertAfter($textarea);

            var iDoc = this.$iframe[0].contentWindow.document,
                html = '<html>'
                     +   '<head>'
                     +     '<style type="text/css">* { cursor: pointer !important; }</style>'
                     +   '</head>'
                     +   '<body>'
                     +     $textarea.val()
                     +   '</body>'
                     + '</html>';

            iDoc.open();
            iDoc.write(html);
            iDoc.close();

            $(iDoc).click($.proxy(this, 'initCKEditor'));
        },

        /**
         * Init CKEditor
         */
        initCKEditor: function() {
            var textareaId = this.id;
            ClassicEditor.create(document.querySelector('#'+this.id), this.config)
            .then( editor => {
                //console.log( Array.from( editor.ui.componentFactory.names() ) );

                // When CKEditor is updated, update the textarea so EE will trigger it's auto-save or remove error messages.
                var editor_changed = false;
                editor.model.document.on('change:data', () => {
                    editor_changed = true;
                });
                editor.ui.focusTracker.on('change:isFocused', (evt, name, isFocused) => {
                    if(!isFocused && editor_changed) {
                        editor_changed = false;
                        // Update the textarea's content.
                        document.querySelector('#' + this.id).innerHTML = editor.getData();

                        // Trigger the `onchange` handlers for the textarea so EE will catch the update.
                        $('#' + this.id).change();

                        // We have to focus and blur the real textarea otherwise EE5 won't pick up the change.
                        $('#' + this.id).focus().blur();
                    }
                });
            } );

            if (this.$iframe) {
                this.$iframe.remove();
            }
        }
    }

    if (typeof FluidField !== 'undefined') {
        FluidField.on('rte', 'add', function(row) {
            var field_id = row.find('.rte-textarea').attr('id');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');

            if(defer === 'n') defer = false;

            new Rte(field_id, config_handle, defer);
        });
    }

    /**
     * Load EE File Browser
     */
    Rte.loadEEFileBrowser = function(params, directory, content_type) {
        // Set up the temporary increase of z-indexes.
        var modalZIndex = $('.modal-file').css('z-index'),
            overlayZindex = $('.overlay').css('z-index');

        $('.modal-file').css('z-index', 10012);
        $('.overlay, .app-overlay').css('z-index', 10011);

        if ($('html').css('position') === 'fixed') {
            $('body').css({ position:'fixed', width:'initial' });
        }

        // Set up restoration of original z-indexes.
        var restoreZIndexes = function (){
            $('.modal-file').css({'z-index': modalZIndex});
            $('.overlay').css({'z-index': overlayZindex});
            $('body').css({ position:'initial', width:'initial' });
        };

        var $trigger = $('<trigger class="m-link filepicker" rel="modal-file" href="' + EE.Rte.fpUrl + '"/>').appendTo('body');

        $trigger.FilePicker({
            callback: function(data, references)
            {
                references.modal.find('.m-close').click();
                $('body').off('modal:close', '.modal-file', restoreZIndexes);
            }
        });

        $trigger.click();

        // Set up the listener to restore the z-indexes.
        $('body').on('modal:close', '.modal-file', restoreZIndexes);
    };
})(jQuery);

var Rte_pages_loading = null;
function getPages( queryText ) {
    if (Rte_pages_loading) {
        Rte_pages_loading.abort();
    }
    //we only make request if the previous one had some results
    return new Promise( resolve => {
        var data = {search: queryText};
        Rte_pages_loading = $.ajax({
            url: EE.Rte.pages_autocomplete,
            type: "GET",
            data: data,
            cache: true,
            contentType: "application/json",
            success: function (data, textStatus) {
                const itemsToDisplay = data
                    // Filter out the full list of all items to only those matching the query text.
                    .filter( isItemMatching )
                    // Return 10 items max - needed for generic queries when the list may contain hundreds of elements.
                    .slice( 0, 10 );
                Rte_pages_loading = null;
                itemsToDisplay.push({'id': '@' + queryText.replace(" ", "-"), 'text': '\uFEFF@' + queryText, 'href': null});
                resolve( itemsToDisplay );
            },
            error: function(e) {
                //do nothing
            }
        });
    } );

    // Filtering function - it uses `name` and `username` properties of an item to find a match.
    function isItemMatching( item ) {
        // Make the search case-insensitive.
        const searchString = queryText.toLowerCase();

        // Include an item in the search results if name or username includes the current user input.
        return (
            item.text.toLowerCase().includes( searchString ) ||
            item.href.toLowerCase().includes( searchString )
        );
    }
}

function formatPageLinks( item ) {
    const itemElement = document.createElement( 'div' );

    itemElement.id = `mention-list-item-id-${ item.id }`;
    itemElement.textContent = `${ item.text } `;

    if (typeof(item.extra)!=='undefined' && item.extra!='') {

        const extraElement = document.createElement( 'span' );

        extraElement.classList.add( 'mention-extra' );
        extraElement.textContent = '(' + item.extra + ')';

        itemElement.appendChild( extraElement );
    }

    return itemElement;
}