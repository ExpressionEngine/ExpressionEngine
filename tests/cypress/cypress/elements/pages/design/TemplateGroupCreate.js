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

export default TemplateGroupCreate;