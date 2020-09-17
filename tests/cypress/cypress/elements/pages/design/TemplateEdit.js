import ControlPanel from '../ControlPanel'

class TemplateEdit extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/template/edit';

        this.elements({
            "view_rendered_button": '.form-btns-top a.btn.action',
            "save_button": '.form-btns button.btn[value="edit"]',
            "save_and_close_button": '.form-btns button.btn[value="finish"]',

            //Tabs
            "edit_tab": 'ul.tabs a[rel="t-0"]',
            "notes_tab": 'ul.tabs a[rel="t-1"]',
            "settings_tab": 'ul.tabs a[rel="t-2"]',
            "access_tab": 'ul.tabs a[rel="t-3"]',

            //Edit Tab
            "codemirror": '.CodeMirror',
            "template_data": 'textarea[name="template_data"]',

            //Notes Tab
            "template_notes": 'textarea[name="template_notes"]',

            //Settings Tab
            "name": 'input[name="template_name"]',
            "type": 'input[name="template_type"]',
            "enable_caching": 'a[data-toggle-for="cache"]',
            "refresh_interval": 'input[name="refresh"]',
            "allow_php": 'a[data-toggle-for="allow_php"]',
            "php_parse_stage": 'input[name="php_parse_location"]',
            "hit_counter": 'input[name="hits"]',

            //Access Tab
            "allowed_member_groups": 'div[data-input-value="allowed_member_groups"] input[type="checkbox"]',
            "no_access_redirect": 'div[data-input-value="no_auth_bounce"] input[type="radio"]',
            "enable_http_auth": 'a[data-toggle-for="enable_http_auth"]',
            "template_route": 'input[name="route"]',
            "require_all_variables": 'a[data-toggle-for="route_required"]',
        })
    }
    load_edit_for_template(id) {
        cy.visit(`${this.url}/${id}`)
    }
}
export default TemplateEdit;