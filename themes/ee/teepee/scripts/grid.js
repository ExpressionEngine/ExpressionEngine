(function($) {
    Teepee.gridColConfigs = {};

    var newGridRowCount = 0;

    var gridCloneDestroyTeepee = function(cell) {
        var $textarea = $('textarea', cell),
            $iframe = $('iframe:first', cell);

        // has CKEditor been initialized?
        if (!$iframe.hasClass('teepee')) {

            // Make a clone of the editor DOM
            $(cell).data('ckeClone', $(cell).children('.cke').clone());

            // save the latest HTML value to the textarea
            var id = $textarea.attr('id');

            var editor = CKEDITOR.instances[id];

            editor.updateElement();

            // destroy the CKEDITOR.editor instance
            editor.destroy();

            // make it look like nothing happened
            $textarea.hide();
            $(cell).data('ckeClone').appendTo(cell);
        }
    };

    var gridReinitTeepee = function(cell) {
        if (typeof $(cell).data('ckeClone') != 'undefined') {
            $(cell).data('ckeClone').remove();
            $(cell).data('ckeClone', null);
        }
        onDisplay(cell);
    };

    /**
     * Display
     */
    var onDisplay = function(cell) {
        var rowId = "";
        if (cell.data('row-id')) {
            rowId = cell.data('row-id');
        } else {
            rowId = 'new_row_' + ++newGridRowCount;
        }

        var $textarea = $('textarea', cell),
            config = Teepee.gridColConfigs['col_id_' + cell.data('column-id')],
            id = cell.parents('.grid_field_container').attr('id')+'_'+rowId+'_'+cell.data('column-id')+'_'+Math.floor(Math.random()*100000000);

        id = id.replace(/\[/, '_').replace(/\]/, '');

        $textarea.attr('id', id);

        new Teepee(id, config[0], config[1], cell);
    };

    Grid.bind('teepee', 'display', onDisplay);

    /**
     * Before Sort
     */
    Grid.bind('teepee', 'beforeSort', function(cell){
        gridCloneDestroyTeepee(cell);
    });

    /**
     * After Sort
     */
    Grid.bind('teepee', 'afterSort', function(cell) {
        gridReinitTeepee(cell);
    });

    if (typeof FluidField !== 'undefined') {
        FluidField.on('grid', 'beforeSort', function(cell){

            $('textarea', cell).each(function() {
                if ($(this).closest('td').data('fieldtype') === 'teepee' && $(this).attr('id') !== undefined) {
                    gridCloneDestroyTeepee($(this).closest('td'));
                }
            });
        });

        FluidField.on('grid', 'afterSort', function(cell) {

            $('textarea', cell).each(function() {
                if ($(this).closest('td').data('fieldtype') === 'teepee' && $(this).attr('id') !== undefined) {
                    gridReinitTeepee($(this).closest('td'));
                }
            });
        });
    }
})(jQuery);
