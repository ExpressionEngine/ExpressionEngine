import ControlPanel from '../ControlPanel'

class ChannelFields extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            "create_new": '.section-header a.btn.action',
            "fields": '.tbl-list > li',
            "fields_edit": '.tbl-list > li .main > a',
            "fields_checkboxes": '.tbl-list > li input[type="checkbox"]',
        })
    }

}
export default ChannelFields;