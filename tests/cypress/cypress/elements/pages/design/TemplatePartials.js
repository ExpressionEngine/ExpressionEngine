import ControlPanel from '../ControlPanel'

class TemplatePartials extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/snippets';

        this.elements({
            "create_new_button": '.title-bar__extra-tools a.button--action',
            "keyword_search": '.title-bar .filter-bar .search-input__input',
            "perpage_filter": '.filters ul li:nth-child(2) a',

            "partials": '.table-responsive table tbody tr',
            // "name": 'td:first-child',
            // "all_sites": 'td:nth-child(2)',
            // "manage": 'td:nth-child(3)'
            //     "edit": '.toolbar .edit a',
            //     "find": '.toolbar .find a',
            // "bulk_action_checkbox": 'td:nth-child(4) input',

        })
    }
}
export default TemplatePartials;