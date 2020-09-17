import ControlPanel from '../ControlPanel'

class TemplateManager extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design';

        this.elements({
            "settings_icon": '.section-header__options a.icon--settings',
            "export_icon": '.section-header__options a.icon--export',
            "phrase_search": '.section-header__controls input[name="search"]',
            "search_submit_button": '.section-header__controls input[type="submit"]',
            "create_new_template_button": 'fieldset.right a.btn.action',
            "perpage_filter": '.filters ul li:first-child a',
            "template_groups": '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li',
            // "template_groups": this.section(
            //     '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li', {
            //         "name": 'a[href*="cp/design/manager"]',
            //         "edit": '.toolbar .edit a',
            //         "remove": '.toolbar .remove a',
            //     }
            // ),
            "default_template_group": '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li.default',
            "active_template_group": '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li.act',
            "templates": '.tbl-wrap table tbody tr',
            // "templates": this.section('.tbl-wrap table tbody tr', {
            //     "name": 'td:first-child',
            //     "type": 'td:nth-child(2)',
            //     "manage": this.section('td:nth-child(3)', {
            //         "view": '.toolbar .view a',
            //         "edit": '.toolbar .edit a',
            //         "settings": '.toolbar .settings a',
            //     }),
            //     "bulk_action_checkbox": 'td:nth-child(4) input',
            // }),
        })
    }
}
export default TemplateManager;