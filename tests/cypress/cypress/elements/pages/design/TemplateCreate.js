import ControlPanel from '../ControlPanel'

class TemplateCreate extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/template/create';

        this.elements({
            "save_button": '.form-btns-top button.btn[value="finish"]',
            "save_and_edit_button": '.form-btns-top button.btn[value="edit"]',
            "name": 'input[name="template_name"]',
            "type": 'input[name="template_type"]',
            "duplicate_existing_template": 'div[data-input-value="template_id"] input[type="radio"]',
        })
    }
}


export default TemplateCreate;