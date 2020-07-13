import ControlPanel from '../ControlPanel'

class FieldGroupForm extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields/groups/create';

        this.selectors = Object.assign(this.selectors, {
            "name": 'input[name="field_label"]',
            "submit": 'button[value="save"]',
        })
    }


}
export default FieldGroupForm;