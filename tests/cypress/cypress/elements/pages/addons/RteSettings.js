import ControlPanel from '../ControlPanel'

class RteSettings extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons/settings/rte';

        this.selectors = Object.assign(this.selectors, {
            "title": '.section-header__title',
            "headings": 'form h1',

            // Settings View
            "rte_enabled": 'input[name=rte_enabled]',
            "rte_enabled_toggle": 'a[data-toggle-for=rte_enabled]',
            "default_tool_set": 'input[name="rte_default_toolset_id"]',
            "save_settings_button": 'div.form-btns.form-btns-top input.btn[type="submit"]',

            "create_new_button": 'div.tbl-ctrls form fieldset.tbl-search a.btn.action',
            "tool_sets": 'div.tbl-ctrls form div.tbl-wrap table tr',
            "tool_set_name_header": 'div.tbl-ctrls form div.tbl-wrap table tr th:first-child',
            "status_header": 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)',
            "manage_header": 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)',
            "checkbox_header": 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(4)',

            "tool_set_names": 'div.tbl-ctrls form div.tbl-wrap table tr td:first-child',
            "statuses": 'div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)',

            "bulk_action": 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
            "action_submit_button": 'form fieldset.tbl-bulk-act input.submit',

            // Tool Set View
            "tool_set_name": 'input[name="toolset_name"]',
            "choose_tools": 'div[data-input-value="tools"] input[type="checkbox"]',
            "tool_set_save_and_close_button": 'div.form-btns.form-btns-top button[value="save_and_close"]',
            "tool_set_save_button": 'div.form-btns.form-btns-top button[value="save"]'
        })
    }

    confirmSettings() {
        this.get('breadcrumb').contains('Add-On Manager')
        this.get('breadcrumb').contains('Rich Text Editor Configuration')

        this.get('headings').eq(0).contains('Rich Text Editor Configuration')
        this.get('headings').eq(1).contains('Available Tool Sets')

        this.get('rte_enabled').should('exist')
        this.get('rte_enabled_toggle').should('exist')
        this.get('default_tool_set').should('exist')
        this.get('save_settings_button').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
    }

    confirmToolset() {
        this.get('breadcrumb').contains('Add-On Manager')
        this.get('breadcrumb').contains('Rich Text Editor Configuration')
        this.get('breadcrumb').contains('RTE Tool Set')

        this.get('headings').eq(0).contains('RTE Tool Set')

        this.get('rte_enabled').should('exist')
        this.get('rte_enabled_toggle').should('exist')
        this.get('default_tool_set').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
        this.get('tool_set_save_and_close_button').should('not.exist')
    }

}

export default RteSettings;