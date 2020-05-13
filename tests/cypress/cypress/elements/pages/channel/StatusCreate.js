import ControlPanel from '../ControlPanel'

class ChannelManager extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/channels';

        this.elements({
            "status": 'input[name=status]',
            "highlight": 'input[name=highlight]',
            "color_panel": 'div.colorpicker__panel',
            "status_access": 'input[name="status_access[]"]',

            "submit_buttons": ".app-modal:visible .form-btns .btn",
            "status_names": '[data-input-value="statuses"] .checkbox-label .status-tag',
        })
    }

    load_view_for_status_group(number) {
        this.open_dev_menu()
        cy.contains('Channels').click()
        cy.get('ul.list-group li:nth-child(' + number + ') a.list-item__content').first().click()
        cy.get('.js-tab-button:contains("Statuses")').click()
    }

    load_create_for_status_group(number) {
        cy.contains('Add Status').click()
    }

    load_edit_for_status(number) {
        cy.get('[data-input-value="statuses"] .checkbox-label:nth-child(' + number + ') .flyout-edit').click()
    }

}
export default ChannelManager;