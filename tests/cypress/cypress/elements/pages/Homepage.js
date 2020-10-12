import ControlPanel from './ControlPanel'

const { _, $ } = Cypress

class Homepage extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/homepage';
        this.selectors = Object.assign(this.selectors, {
            "home": 'a.nav-home',
            "dashboard": '.dashboard'
        })
    }

    toggleSpam(state) {
        this.get('nav').contains('Add-Ons').click().then(function() {
            let canInstall = $('a[data-post-url*="cp/addons/install/spam"]').length > 0

            if (state == 'on' && canInstall) {
                cy.get('a[data-post-url*="cp/addons/install/spam"]').click()
            } else if (state == 'off' && !canInstall) {
                let btn = cy.get('.add-on-card:contains("Spam")').find('.js-dropdown-toggle')
                btn.click()
                btn.next('.dropdown').find('a:contains("Uninstall")').click()
            }
            cy.visit(this.url);
        }.bind(this))
    }
}

export default Homepage;