import ControlPanel from './ControlPanel'

const { _, $ } = Cypress

class Homepage extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/homepage';
        this.selectors = Object.assign(this.selectors, {
            "home": 'a.nav-home',
            "comment_box": '.home-layout .col-group:nth-child(2) .col:first-child .box',
            "comment_info": '.home-layout .col-group:nth-child(2) .col:first-child .box .info'
        })
    }

    toggleSpam(state) {
        this.get('developer_menu').click()
        cy.contains('Add-Ons').click().then(function() {
            let canInstall = $('a[data-post-url*="cp/addons/install/spam"]').length > 0

            if (state == 'on' && canInstall) {
                cy.get('a[data-post-url*="cp/addons/install/spam"]').click()
            } else if (state == 'off' && !canInstall) {
                cy.get('input[value="spam"]').click()
                cy.get('select[name="bulk_action"]').select('remove')
                cy.get('.tbl-bulk-act button').click()
                cy.get('.modal form input.btn[type="submit"]').contains('Confirm, and Uninstall').click()
            }
            cy.visit(this.url);
        }.bind(this))
    }
}

export default Homepage;