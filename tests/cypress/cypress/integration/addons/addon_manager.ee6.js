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

    it('shows the Add-On Manager', function() {
        page.get('first_party_section').should('exist')

    })

    it('can install a single add-on', function() {


        page.get('uninstalled_addons').eq(1).then(function(el) {
            const addon_name = el.find('.add-on-card__title').contents().filter(function(){ return this.nodeType == 3; }).text().trim();
            cy.log(addon_name);

            el.find('.add-on-card__button a').first().click();
            cy.hasNoErrors()

            page.hasAlert()
            page.get('alert').first().invoke('text').should('include', 'Add-Ons Installed')
            page.get('alert').first().invoke('text').should('include', addon_name)

            page.get('uninstalled_addons').find('.add-on-card__title').should('not.contain', addon_name)
        })
    })

    // The settings buttons "work"(200 response)
    it('can navigate to a settings page', function() {
        const btn = page.get('first_party_section').find('.add-on-card:contains("Rich Text Editor")').find('.js-dropdown-toggle')
        btn.click()
        btn.next('.dropdown').find('a:contains("Settings")').click()
        cy.hasNoErrors()
    })

    // The guide buttons "work"(200 response)
    it('can navigate to a manual page', function() {
        let btn = page.get('first_party_section').find('.add-on-card:contains("Rich Text Editor")').find('.js-dropdown-toggle')
        btn.click()
        btn.next('.dropdown').find('a:contains("Settings")').click()
        cy.hasNoErrors()

        /*cy.visit(page.url);

        btn = page.get('first_party_section').find('.add-on-card').first().find('.js-dropdown-toggle')
        btn.click()
        btn.next('.dropdown').find('a:contains("Manual")').click()
        cy.hasNoErrors()*/
    })

    it('can uninstall add-ons', function() {

        page.get('addons').first().then((addon_card) => {
            const addon_name = addon_card.find('.add-on-card__title').contents().filter(function(){ return this.nodeType == 3; }).text().trim();
            cy.log(addon_name);
            let btn = addon_card.find('.js-dropdown-toggle')
            btn.click()
            btn.next('.dropdown').find('a:contains("Uninstall")').click()

            page.get('modal_submit_button').contains('Confirm, and Uninstall').click() // Submits a form
            cy.hasNoErrors();

            // The filter should not change
            page.hasAlert()

            page.get('alert').contains("Add-Ons Uninstalled")
            page.get('alert').contains(addon_name);
        })

    })


})
