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
        })
    }

    load() {
        // self.open_dev_menu
        cy.contains('Channel Manager').click()
        cy.contains('Status Groups').click()
        cy.contains('Create New').click()
    }

    load_view_for_status_group(number) {
        // self.open_dev_menu
        cy.contains('Channel Manager').click()
        cy.contains('Status Groups').click()

        find('tbody tr:nth-child(' + number + ') li.txt-only a').click()
    }

    load_create_for_status_group(number) {
        cy.contains('Create New').click()
    }

    load_edit_for_status(number) {
        find('tbody tr:nth-child(' + number + ') li.edit a').click()
    }

}
export default ChannelManager;