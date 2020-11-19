import ControlPanel from '../ControlPanel'

class GridSettings extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            "create_new": '.sidebar a.button.left',
            "field_groups": '.folder-list > li',
            "field_groups_edit": '.folder-list li.edit a',
            "field_groups_fields": '.folder-list > li > a',
        })
    }

    column(number) {
        number = number + 1 // Skip over "no results" div
        return cy.get('.fields-grid-setup .fields-grid-item:nth-child(' + number.to_s + ')')
            // GridSettingsColumn.new(node)
    }

}
export default GridSettings;