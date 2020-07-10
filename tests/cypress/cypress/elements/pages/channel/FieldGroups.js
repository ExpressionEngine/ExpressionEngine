import ControlPanel from '../ControlPanel'
import FieldGroupForm from './FieldGroupForm'
const form = new FieldGroupForm

class FieldGroups extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            //"create_new": '.sidebar a.button--action.left',
            "create_new": 'a[href="admin.php?/cp/fields/create"]',

            "field_groups": '.folder-list > div',
            //"field_groups_edit": '.folder-list div li.edit a',
            "field_groups_edit": 'a[title="Edit"]',

            "field_groups_fields": '.folder-list > div > a',
        })
    }

    save_field_group(name) {
        form.get('name').clear().type(name)
        form.get('submit').first().click()
    }

}
export default FieldGroups;