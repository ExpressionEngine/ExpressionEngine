import ControlPanel from '../ControlPanel'

class TemplateGroupCreate extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/group/create';

        this.elements({
            "save_button": '.form-btns-top input.btn',
            "name": 'input[type=text][name=group_name]',
            "duplicate_existing_group": 'div[data-input-value="duplicate_group"] input[type="radio"]',
            "is_site_default": 'a[data-toggle-for="is_site_default"]',
        })
    }
}

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
export default { TemplateGroupCreate, TemplateGroupEdit }
// export default TemplateGroupCreate;