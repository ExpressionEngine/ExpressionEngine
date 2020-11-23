import ControlPanel from '../ControlPanel'

class TemplatePartialForm extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/design/snippets';

        this.elements({
            "save_button": '.form-btns-top input.btn',

            "name": 'input[name="snippet_name"]',
            "contents": '.CodeMirror',
            "contents_editor": '.CodeMirror textarea',
            "contents_textarea": 'textarea[name="snippet_contents"]',
            "site_id": 'input[type="radio"][name="site_id"]',
        })
    }
}
export default TemplatePartialForm;