import ControlPanel from '../ControlPanel'

class ChannelFields extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            "create_new": '.main-nav__toolbar a.button--action',
            "fields": '.list-group > .list-item',
            "fields_edit": '.list-group > .list-item > a.list-item__content',
            "fields_checkboxes": '.list-group > .list-item input[type="checkbox"]',
        })
    }

}
export default ChannelFields;