import ControlPanel from '../ControlPanel'
const FieldGroupForm = require('./FieldGroupForm')

class FieldGroups extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            "create_new": '.sidebar a.btn.left',
            "field_groups": '.folder-list > li',
            "field_groups_edit": '.folder-list li.edit a',
            "field_groups_fields": '.folder-list > li > a',
        })
    }

    save_field_group(name) {
        let form = new FieldGroupForm
        form.get('name').clear().type(name)
        form.get('submit').first().click()
    }

}
export default FieldGroups;