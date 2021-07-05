(function($) {
    Rte.gridColConfigs = {};

    var newGridRowCount = 0;

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
            config = Rte.gridColConfigs['col_id_' + cell.data('column-id')],
            id = cell.parents('.grid-field').attr('id')+'_'+rowId+'_'+cell.data('column-id')+'_'+Math.floor(Math.random()*100000000);

        id = id.replace(/\[/g, '_').replace(/\]/g, '');

        $textarea.attr('id', id);

        new Rte(id, config[0], config[1], cell);
    };

    Grid.bind('rte', 'display', onDisplay);

})(jQuery);
