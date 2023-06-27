/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
const page = new AddonManager;

context('Add-On Manager', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('filesystem:copy', { from: 'support/add-on-manager/test_*', to: '../../system/user/addons/' })
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/addons/test_*')
    })

    beforeEach(function() {
        cy.authVisit(page.url);

        page.get('title').contains('Add-Ons')
    })

    it('Check that Add-On Manager page opens and has no errors', function() {
        page.get('first_party_section').should('exist')
        cy.hasNoErrors()
    })

    it('Can install a single add-on', function() {


        page.get('uninstalled_addons').eq(1).then(function(el) {
            const addon_name = el.find('.add-on-card__title').contents().filter(function(){ return this.nodeType == 3; }).text().trim();
            cy.log(addon_name);

            cy.get(el).find('.add-on-card__button a').trigger('click')
            cy.hasNoErrors()

            page.hasAlert()
            cy.get('div.app-notice.app-notice--inline').first().invoke('text').should('include', 'Add-Ons Installed')
            cy.get('div.app-notice.app-notice--inline').first().invoke('text').should('include', addon_name)

            page.get('uninstalled_addons').find('.add-on-card__title').should('not.contain', addon_name)
        })
    })

    // The settings buttons "work"(200 response)
    it('Can navigate to add-on settings page', function() {
        const btn = page.get('first_party_section').find('.add-on-card:contains("Rich Text Editor")').find('.js-dropdown-toggle')
        btn.click()
        btn.next('.dropdown').find('a:contains("Settings")').click()
        cy.hasNoErrors()
    })

    // The guide buttons "work"(200 response)
    it('Install plugin and navigate to a manual page', function() {
        page.get('first_party_addons').find('.add-on-card:contains("HTTP Header") a').click()
        let btn = page.get('first_party_section').find('.add-on-card:contains("HTTP Header")').find('.js-dropdown-toggle')
        btn.click()
        btn.next('.dropdown').find('a:contains("Manual")').click()
        cy.hasNoErrors()
        cy.get('h2').should('contain', 'Usage')
    })

    it('Can uninstall single add-on', function() {

        page.get('addons').first().then((addon_card) => {
            const addon_name = addon_card.find('.add-on-card__title').contents().filter(function(){ return this.nodeType == 3; }).text().trim();
            cy.log(addon_name);
            let btn = addon_card.find('.js-dropdown-toggle')
            cy.get(btn).trigger('click')
            cy.get(btn).next('.dropdown').find('a:contains("Uninstall")').trigger('click')

            page.get('modal_submit_button').contains('Confirm, and Uninstall').click() // Submits a form
            cy.hasNoErrors();

            // The filter should not change
            page.hasAlert()

            page.get('alert').contains("Add-Ons Uninstalled")
            page.get('alert').contains(addon_name);
        })

    })


})
