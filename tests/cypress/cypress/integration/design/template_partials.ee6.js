/// <reference types="Cypress" />

import TemplatePartials from '../../elements/pages/design/TemplatePartials';
import TemplatePartialForm from '../../elements/pages/design/TemplatePartialForm';
const page = new TemplatePartials;
const form = new TemplatePartialForm;
const { _, $ } = Cypress

context('Template Partials', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('displays', function() {
        page.get('partials').its('length').should('eq', 13)
    })

    it('can filter by keyword', function() {
        page.get('keyword_search').first().clear().type('global').type('{enter}')

        cy.hasNoErrors()

        page.get('partials').its('length').should('eq', 8)
    })

    it('can find templates that use a partial', function() {
        page.get('partials').eq(6).find('a[title="find"]').click()

        cy.hasNoErrors()

        page.get('page_heading').contains('Search Results')
        page.get('page_heading').contains('{global_stylesheets}')
        page.get('partials').its('length').should('eq', 9)
    })

    it('can navigate to edit form', function() {
        
        page.get('partials').eq(6).find('a[title="Edit"]').click()
        cy.hasNoErrors()
    })

    it('should validate the form', function() {
        //page.get('create_new_button').click()
        cy.get('a').contains('Create New').first().click()

        form.get('name').clear().type('lots of neat stuff').trigger('blur')

        page.hasError(form.get('name'), 'The name you submitted may only contain alpha-numeric characters, underscores, and dashes')
       
    })

    it('can create a new partial', function() {
        // skip 'Cannot figure out how to populate a codemirror form element', () => {

        //page.get('create_new_button').click()
        cy.get('a').contains('Create New').first().click()

        cy.hasNoErrors()

        form.get('name').clear().type('rspec-test')

        form.get('contents').should('exist')
        form.get('contents_editor').should('exist')

        form.get('contents').click()
        form.get('contents_editor').type('Lorem ipsum...')

        //form.get('save_button').first().click()
        cy.get('input').contains('Save Partial').first().click()

        cy.hasNoErrors()

        page.hasAlert()
        page.get('alert').contains('Template Partial Created')
        page.get('alert').contains('rspec-test')
    })

    it('can remove a partial', function() {
        page.get('partials').its('length').then((length) => {
            page.get('partials').eq(1).find('td:nth-child(4) input').click()


            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Delete')
            page.get('action_submit_button').click()

            //page.get('modal_submit_button').click()
            cy.get('input[value="Confirm and Delete"]').filter(':visible').first().click()

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('partials').its('length').should('eq', length-1)
        })
    })
})