import ControlPanel from '../ControlPanel'

class TemplateVariables extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/variables';

        this.elements({
            "create_new_button": 'fieldset.right a.btn.action',

            "keyword_search": '.filters ul li:first-child input',
            "perpage_filter": '.filters ul li:nth-child(2) a',

            "variables": '.tbl-wrap table tbody tr',
            // "name": 'td:first-child',
            // "all_sites": 'td:nth-child(2)',
            // "manage": 'td:nth-child(3)',
            //   "edit": '.toolbar .edit a',
            //   "find": '.toolbar .find a',
            // "bulk_action_checkbox": 'td:nth-child(4) input',
        })
    }
}
export default TemplateVariables;