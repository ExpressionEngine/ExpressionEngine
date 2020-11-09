import ControlPanel from '../ControlPanel'

class TemplateGroupEdit extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/group/edit';

        this.elements({
            "save_button": '.form-btns-top input.btn',
            "name": 'input[type=text][name=group_name]',
            "is_site_default": 'a[data-toggle-for="is_site_default"]',
        })
    }
    load_edit_for_group(name) {
        cy.visit(`${this.url}/${name}`)
    }
}
export default TemplateGroupEdit;
// export default TemplateGroupCreate;