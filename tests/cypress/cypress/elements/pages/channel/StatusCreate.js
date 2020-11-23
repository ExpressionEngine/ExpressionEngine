import ControlPanel from '../ControlPanel'

class ChannelManager extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.elements({
            "status": 'input[name=status]',
            "highlight": 'input[name=highlight]',
            "color_panel": 'div.minicolors-panel',
            "status_access": 'input[name="status_access[]"]',

            "submit_buttons": ".app-modal:visible .form-btns .btn",
            "status_names": '[data-input-value="statuses"] label .status-tag',
        })
    }

    load_view_for_status_group(number) {
        cy.visit('admin.php?/cp/channels')
        cy.get('ul.tbl-list li:nth-child(' + number + ') .main a').first().click()
        cy.get('.tabs a:contains("Statuses")').click()
    }

    load_create_for_status_group(number) {
        cy.contains('Add Status').click()
    }

    load_edit_for_status(number) {
        cy.get('[data-input-value="statuses"] label:nth-child(' + number + ')>a').click()
    }

}
export default ChannelManager;
