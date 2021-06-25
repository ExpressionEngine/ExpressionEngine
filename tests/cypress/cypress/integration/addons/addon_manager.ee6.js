/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
const page = new AddonManager;

context('Add-On Manager', () => {

    before(function () {
        cy.task('db:seed')
        cy.task('filesystem:copy', { from: 'support/add-on-manager/test_*', to: '../../system/user/addons/' })
    })

    after(function () {
        cy.task('filesystem:delete', '../../system/user/addons/test_*')
    })

    beforeEach(function () {
        cy.authVisit(page.url);

        page.get('title').contains('Add-Ons')
    })

    it('shows the Add-On Manager', function () {
        page.get('head').should('exist')


    })

    it('can install a single add-on', function () {
        page.get('uninstalled').should('exist')
        var name = ""
        page.get('uninstalled').first().find("strong").then(($name) => {

            // store the addons's text name
            name = $name.text()
            cy.log(name)
            page.get('uninstalled').first().find("td").find("a.button").click()
            cy.hasNoErrors()
            page.hasAlert()
            page.get('alert').first().invoke('text').should('include', 'Add-Ons Installed')
            page.get('alert').first().invoke('text').should('include', name)
        })
    })

    // The settings buttons "work"(200 response)
    it('can navigate to a settings page', function () {
        cy.get('.settings').first().click()
        cy.hasNoErrors()
    })


    it('can bulk install add-ons and can navigate to a manual page', function(){
        cy.get('#tbl_6092f7c1be4f4-select-all').click()
        cy.get('.select-popup').select("Install")
        cy.get('.bulk-action-bar > .button').click()

        cy.wait(100)

        
        cy.get('.modal-confirm-install > .modal > form > .dialog__actions > .dialog__buttons > .form-btns > .button').click()
        
        page.get('alert').first().invoke('text').should('include', 'Add-Ons Installed')

        cy.get('.manual').first().click()
        cy.hasNoErrors()


    })

    it('can uninstall add-ons', function () {

        cy.get('td.app-listing__cell').first().find("a").contains('Uninstall').first().click()
        page.get('alert').first().invoke('text').should('include', 'Add-Ons Uninstalled')
    })

    it('can bulk uninstall add-ons', function(){
        cy.get('#tbl_6092f7c1be4f4-select-all').click()
        cy.get('.select-popup').select("Delete")
        cy.get('.bulk-action-bar > .button').click()

        cy.wait(100)

        cy.get('.modal-confirm-remove > .modal > form > .dialog__actions > .dialog__buttons > .form-btns > .button').click()       
        page.get('alert').first().invoke('text').should('include', 'Add-Ons Uninstalled')

    })


})
