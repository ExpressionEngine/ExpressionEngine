import ControlPanel from '../ControlPanel'

class BlockAndAllow extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons/settings/block_and_allow';

        this.all_there = {
            'blocked_tab_switch': '.tab-bar__tab:contains("Blocked")',
            'blockedlist_ip': 'form textarea[name=blockedlist_ip]',
            'blockedlist_agent': 'form textarea[name=blockedlist_agent]',
            'blockedlist_url': 'form textarea[name=blockedlist_url]',

            'allowed_tab_switch': '.tab-bar__tab:contains("Allowed")',
            'allowedlist_ip': 'form textarea[name=allowedlist_ip]',
            'allowedlist_agent': 'form textarea[name=allowedlist_agent]',
            'allowedlist_url': 'form textarea[name=allowedlist_url]',

            'settings_tab_switch': '.tab-bar__tab:contains("Settings")',
            'htaccess_path': 'form input[name=htaccess_path]',
        }

        this.selectors = Object.assign(this.selectors, this.all_there)
    }

    confirmSettings() {
        //this.get('breadcrumb').contains('Add-Ons')
        this.get('breadcrumb').contains('Rich Text Editor')

        cy.get('h1').contains('Rich Text Editor')

        this.get('default_tool_set').should('exist')
        this.get('save_settings_button').should('exist')
        this.get('create_new_button').should('exist')
        this.get('tool_sets').should('exist')

        this.get('tool_set_name').should('not.exist')
        this.get('choose_tools').should('not.exist')
    }

}

export default BlockAndAllow;
