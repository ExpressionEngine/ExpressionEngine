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

        if (typeof(this.config.typing) !== 'undefined' && typeof(this.config.typing.transformations) !== 'undefined' && typeof(this.config.typing.transformations.extra) !== 'undefined') {
            for (const index in this.config.typing.transformations.extra) {
                var value = this.config.typing.transformations.extra[index];
                if (typeof value.from !== 'undefined' && value.from.indexOf('/') === 0 && value.from.lastIndexOf('$/') === value.from.length - 2) {
                    this.config.typing.transformations.extra[index].from = new RegExp(value.from.substring(1, value.from.length - 2) + '$');
                }
            }
        }

        if (typeof defer == "undefined") {
            this.defer = this.$element.data('defer') == "y";
        } else {
            this.defer = defer;
        }

        if (this.defer) {
            this.showIframe(this.config.type);
        } else if (this.config.type == 'redactor') {
            this.initRedactor();
        } else if (this.config.type == 'redactorX') {
            this.initRedactorX();
        } else {
            this.initCKEditor();
        }
    };

    Rte.prototype = {
        /**
         * Show Iframe
         */
        showIframe: function(type) {
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

            if (type == 'ckeditor') {
                $(iDoc).click($.proxy(this, 'initCKEditor'));
            } else if (type == 'redactor') {
                $(iDoc).click(() => {this.initRedactor();});
            } else {
                $(iDoc).click(() => {this.initRedactorX();});
            }
        },

        /**
         * Init Redactor
         */
        initRedactor: function() {
            var config = typeof this.config === 'string'
                            ? JSON.parse(this.config)
                            : this.config;
            config.callbacks = {
                blur: function(e) {
                    $('#' + this.id).trigger('change');
                },
                keyup: function(e) {
                    $("[data-publish] > form").trigger("entry:startAutosave")
                }
            };
            $R('#' + this.id, config);

            if (this.$iframe) {
                this.$iframe.remove();
            }
        },

        /**
         * Init RedactorX
         */
        initRedactorX: function() {
            var config = typeof this.config === 'string'
                            ? JSON.parse(this.config)
                            : this.config;
            var id = this.id;
            config.subscribe = {
                'editor.blur': function(e) {
                    $('#' + id).trigger('change');
                },
                'editor.keyup': function(e) {
                    $("[data-publish] > form").trigger("entry:startAutosave")
                }
            };

            RedactorX('#' + this.id, config);

            if (this.$iframe) {
                this.$iframe.remove();
            }
        },

        /**
         * Init CKEditor
         */
        initCKEditor: function() {
            var textareaId = this.id;
            ClassicEditor.create(document.querySelector('#'+this.id), this.config)
            .then( editor => {
                // When CKEditor is updated, update the textarea so EE will trigger it's auto-save or remove error messages.
                editor.model.document.on('change:data', (evt, data) => {
                    editor.updateSourceElement();
                    $("[data-publish] > form").trigger("entry:startAutosave");
                });
                editor.ui.focusTracker.on('change:isFocused', (evt, name, isFocused) => {
                    if(!isFocused) {
                        $('#' + this.id).trigger('change').trigger('focus').trigger('blur');
                    }
                });
            } );

            if (this.$iframe) {
                this.$iframe.remove();
            }
        }
    }

    if (typeof FluidField === "object") {
        FluidField.on('rte', 'add', function(row) {
            var field_id = row.find('.rte-textarea').attr('id');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');

            if(defer === 'n') defer = false;

            new Rte(field_id, config_handle, defer);
        });
    }

    
    Rte.gridColConfigs = {};
    var newGridRowCount = 0;

    var rteOnGridDisplay = function(cell) {
        var rowId = "";
        if (cell.data('row-id')) {
            rowId = cell.data('row-id');
        } else {
            rowId = 'new_row_' + ++newGridRowCount;
        }

        var $textarea = $('textarea', cell),
            config = Rte.gridColConfigs['col_id_' + cell.data('column-id')],
            id = cell.parents('.grid-field').attr('id')+'_'+rowId+'_'+cell.data('column-id')+'_'+Math.floor(Math.random()*100000000);

        id = id.replace(/\[/g, '_').replace(/\]/g, '');

        $textarea.attr('id', id);

        new Rte(id, config[0], config[1], cell);
    };

    Grid.bind('rte', 'display', rteOnGridDisplay);

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
            $('.modal-file').css({'z-index': ''});
            $('.overlay, .app-overlay').css({'z-index': ''});
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

        if(item.text) { //check if item.text not empty or not equal null

            // Include an item in the search results if name or username includes the current user input.
            return (
                item.text.toLowerCase().includes( searchString ) ||
                item.href.toLowerCase().includes( searchString )
            );
        }
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
