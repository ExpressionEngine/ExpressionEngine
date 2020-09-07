import ControlPanel from '../ControlPanel'

class TemplateEdit extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/template/edit';

        this.elements({
            "view_rendered_button": '.form-btns .button--action',
            "save_button": '.form-btns button.button[value="edit"]',
            "save_and_close_button": '.form-btns button.button[value="finish"]',

            //Tabs
            "edit_tab": '.tab-bar__tabs [rel="t-0"]',
            "notes_tab": '.tab-bar__tabs [rel="t-1"]',
            "settings_tab": '.tab-bar__tabs [rel="t-2"]',
            "access_tab": '.tab-bar__tabs [rel="t-3"]',

            //Edit Tab
            "codemirror": '.CodeMirror',
            "template_data": 'textarea[name="template_data"]',

            //Notes Tab
            "template_notes": 'textarea[name="template_notes"]',

            //Settings Tab
            "name": 'input[type!=hidden][name="template_name"]',
            "type": 'input[type!=hidden][name="template_type"]',
            "enable_caching": '[data-toggle-for="cache"]',
            "refresh_interval": 'input[type!=hidden][name="refresh"]',
            "allow_php": '[data-toggle-for="allow_php"]',
            "php_parse_stage": 'input[type!=hidden][name="php_parse_location"]',
            "hit_counter": 'input[type!=hidden][name="hits"]',

            //Access Tab
            "allowed_member_groups": 'div[data-input-value="allowed_roles"] input[type="checkbox"]',
            "no_access_redirect": 'div[data-input-value="no_auth_bounce"] input[type="radio"]',
            "enable_http_auth": '[data-toggle-for="enable_http_auth"]',
            "template_route": 'input[type!=hidden][name="route"]',
            "require_all_variables": '[data-toggle-for="route_required"]',
        })
    }
    load_edit_for_template(id) {
        cy.visit(`${this.url}/${id}`)
    }
}
export default TemplateEdit;