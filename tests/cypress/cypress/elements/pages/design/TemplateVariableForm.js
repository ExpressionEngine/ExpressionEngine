import ControlPanel from '../ControlPanel'

class TemplateVariableForm extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/variables';

        this.elements({
            "save_button": '.form-btns-top input.btn',
            "name": 'input[name="variable_name"]',
            "contents": '.CodeMirror',
            "contents_editor": '.CodeMirror textarea',
            "contents_textarea": 'textarea[name="variable_contents"]',
            "site_id": 'input[type="radio"][name="site_id"]',
        })
    }
}
export default TemplateVariableForm;