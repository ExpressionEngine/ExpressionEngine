function cloneDestroyWygwam(cell) {
    var $textarea = $('textarea', cell),
        $iframe = $('iframe:first', cell);

    // has CKEditor been initialized?
    if (!$iframe.hasClass('wygwam')) {
        // Make a clone of the editor DOM
        $(cell).data('ckeClone', $(cell).children('.cke').clone());

        // save the latest HTML value to the textarea
        var id = $textarea.attr('id'),
            editor = CKEDITOR.instances[id];

        editor.updateElement();

        // destroy the CKEDITOR.editor instance
        editor.destroy();

        // make it look like nothing happened
        $textarea.hide();
        $(cell).data('ckeClone').appendTo(cell);
    }
}

function reinitWygwam(cell) {
    if (typeof $(cell).data('ckeClone') != 'undefined') {
        $(cell).data('ckeClone').remove();
        $(cell).data('ckeClone', null);
    }

    var field_id = $(cell).find('.wygwam textarea').attr('id');
    var config_handle = $('#'+field_id).data('config');
    var defer = $('#'+field_id).data('defer');

    if(defer === 'n') defer = false;

    new Wygwam(field_id, config_handle, defer);
}

function refreshWygwam() {
    $('.wygwam-textarea').each(function() {
        if($(this).parent().hasClass('matrix') === true || $(this).parent().hasClass('wygwam') === true || $(this).parent().data('fieldtype') == 'wygwam') {
            var field_id = ($(this).attr('id')).replace('cke_', '');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');
            var editor = CKEDITOR.instances[field_id];

            if(defer === 'n') {
                defer = false;
            } else {
                // if (editor !== undefined) {
                //     editor.destroy();
                // }

                if ($(this).siblings('iframe')) {
                    $(this).siblings('iframe').remove();
                }
            }

            new Wygwam(field_id, config_handle, defer);
        }
    });

    // Bloqs Live Preview Re-init
    $('[data-fieldtype="wygwam"]').each(function() {
        var field_id = $(this).find('textarea').attr('id');

        if (field_id) {
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');
            var editor = CKEDITOR.instances[field_id];

            if(defer === 'n') {
                defer = false;
            } else {
                // if (editor !== undefined) {
                //     editor.destroy();
                // }

                if ($(this).find('textarea').siblings('iframe')) {
                    $(this).find('textarea').siblings('iframe').remove();
                }
            }

            new Wygwam(field_id, config_handle, defer);
        }
    });
}

window.Wygwam;

(function($) {

    /**
     * Wygwam
     */
    Wygwam = function(id, config, defer) {
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

        this.config = (Wygwam.configs[config] || Wygwam.configs['default']);

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

    Wygwam.prototype = {
        /**
         * Show Iframe
         */
        showIframe: function() {
            var width = (this.config.width ? this.config.width.toString() : '100%'),
                height = (this.config.height ? this.config.height.toString() : '200'),
                css = (this.config.contentsCss ? this.config.contentsCss : Wygwam.themeUrl+'lib/ckeditor/contents.css'),
                $textarea = this.$element.hide();

            if (width.match(/\d$/)) width += 'px';
            if (height.match(/\d$/)) height += 'px';

            this.$iframe = $('<iframe class="wygwam" style="width:'+width+'; height:'+height+';" frameborder="0" />').insertAfter($textarea);

            var iDoc = this.$iframe[0].contentWindow.document,
                html = '<html>'
                     +   '<head>'
                     +     '<link rel="stylesheet" type="text/css" href="'+css+'" />'
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
            ClassicEditor.create(document.querySelector('#'+this.id), this.config)
            .then( editor => {
                //console.log( Array.from( editor.ui.componentFactory.names() ) );
            } );

            if (this.$iframe) {
                this.$iframe.remove();
            }


            /*var editor = CKEDITOR.instances[];
            if (editor) {
                editor.destroy(true);
            }



            CKEDITOR.replace(this.id, this.config); //this.$element[0]*/
        }
    }

    // When CKEditor is updated, update the textarea so EE will trigger it's auto-save or remove error messages.
    /*CKEDITOR.on('instanceCreated', function(e) {
        e.editor.on('change', function (event) {
            // Update the textarea's content.
            this.updateElement();

            // Trigger the `onchange` handlers for the textarea so EE will catch the update.
            $(this.element.$).change();

            // We have to focus and blur the real textarea otherwise EE5 won't pick up the change.
            $('#' + this.element.$.id).focus().blur();
        });
    });*/

    if (typeof FluidField !== 'undefined') {
        FluidField.on('wygwam', 'add', function(row) {
            var field_id = row.find('.wygwam textarea').attr('id');
            var config_handle = $('#'+field_id).data('config');
            var defer = $('#'+field_id).data('defer');

            if(defer === 'n') defer = false;

            new Wygwam(field_id, config_handle, defer);
        });

        /**
         * Before Sort
         */
        FluidField.on('wygwam', 'beforeSort', function(cell){
            cloneDestroyWygwam(cell);
        });

        /**
         * After Sort
         */
        FluidField.on('wygwam', 'afterSort', function(cell) {
            reinitWygwam(cell);
        });
    }

    $(document).on('entry:preview-open', refreshWygwam);
    $(document).on('entry:preview-close', refreshWygwam);

    Wygwam.configs = {};
    Wygwam.assetIds = [];

    /**
     * Load Assets Sheet
     */
    Wygwam.loadAssetsSheet = function(params, filedir, kind) {
        var sheet = new Assets.Sheet({
            filedirs: (filedir == 'all' ? filedir : [filedir]),
            kinds: (kind == 'any' ? kind : [kind]),

            onSelect: function(files) {
                if (files[0].url) {
                    var cacheBustUrl = files[0].url + '?cachebuster:' + parseInt(Math.random() * 100, 10);

                    $('body').trigger({
                        type:'filepicker:pick',
                        selection: cacheBustUrl
                    });
                }

                /*if (files[0].id) {
                    if ($.inArray(files[0].id, Wygwam.assetIds) == -1) {
                        for (var instanceName in CKEDITOR.instances) break;
                        var ckeditorContainerElem = CKEDITOR.instances[instanceName].container.$;
                        var $form = $(ckeditorContainerElem).closest('form');

                        $('<input type="hidden" name="wygwam_asset_ids[]"/>').val(files[0].id).appendTo($form);
                        $('<input type="hidden" name="wygwam_asset_urls[]"/>').val(files[0].url).appendTo($form);

                        Wygwam.assetIds.push(files[0].id);
                    }
                }*/
            }
        });

        sheet.show();
    };


    /**
     * Load EE File Browser
     */
    Wygwam.loadEEFileBrowser = function(params, directory, content_type) {
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

        var $trigger = $('<trigger class="m-link filepicker" rel="modal-file" href="' + Wygwam.fpUrl + '"/>').appendTo('body');

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

var wygwam_pages_loading = null;
var searched = [];

function getPages( queryText ) {
    // As an example of an asynchronous action, return a promise
    // that resolves after a 100ms timeout.
    // This can be a server request or any sort of delayed action.
    if (wygwam_pages_loading) {
        wygwam_pages_loading.abort();
    }
    return new Promise( resolve => {
        var data = {search: queryText};
        wygwam_pages_loading = $.ajax({
            url: Wygwam.pages_autocomplete,
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
                wygwam_pages_loading = null;
                searched.push(queryText);
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