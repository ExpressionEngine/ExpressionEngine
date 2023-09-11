/// <reference types="Cypress" />

import TemplateVariables from '../../elements/pages/design/TemplateVariables';
import TemplateVariableForm from '../../elements/pages/design/TemplateVariableForm';
const page = new TemplateVariables;
const form = new TemplateVariableForm;
const { _, $ } = Cypress

context('Template Variables', () => {

    before(function() {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
        cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('List template variables', function() {
        page.get('variables').its('length').should('eq', 14)
    })

    it('Filter template variables by keyword', function() {
        page.get('keyword_search').first().type('html').type('{enter}')

        cy.hasNoErrors()

        page.get('variables').its('length').should('eq', 4) // This also searches data...
    })

    it('Find templates that use a variable', function() {
        page.get('variables').eq(6).find('a[title="find"]').click()

        cy.hasNoErrors()

        page.get('page_heading').contains('Search Results')
        page.get('page_heading').contains('{html_head}')
        page.get('variables').its('length').should('eq', 9) // Yeah, not technically 'variables' but the selectors work
    })

    it('Navigate to variable edit form', function() {
        page.get('variables').eq(6).find('a[title="Edit"]').click()

        cy.hasNoErrors()
    })

    it('Validate variable form', function() {
        cy.get('a').contains('Template Variable').first().click()
        //page.get('create_new_button').click()
        cy.get('a').contains('Create New').first().click()

        form.get('name').clear().type('lots of neat stuff').trigger('blur')

        page.hasError(form.get('name'), 'The name you submitted may only contain alpha-numeric characters, underscores, and dashes')
    })

    it('Create new variable', function() {
        // 'Cannot figure out how to populate a codemirror form element'
        cy.get('a').contains('Template Variable').first().click()

        //page.get('create_new_button').click()
        cy.get('a').contains('Create New').first().click()

        cy.hasNoErrors()

        form.get('name').clear().type('rspec-test')

        form.get('contents').should('exist')
        form.get('contents_editor').should('exist')

        form.get('contents').click()
        form.get('contents_editor').type('Lorem ipsum...')

        //form.get('save_button').first().click()
        cy.get('[value="Save Template Variable"]').first().click()

        cy.hasNoErrors()

        page.hasAlert()
        page.get('alert').contains('Template Variable Created')
        page.get('alert').contains('rspec-test')
    })

    it('Remove a variable', function() {
        page.get('variables').its('length').then((length) => {
            page.get('variables').eq(0).find('td:last-child input').click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Delete')
            page.get('action_submit_button').click()

           // page.get('modal_submit_button').click()
           cy.get('[value="Confirm and Delete"]').filter(':visible').first().click()

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('variables').its('length').should('eq', length-1)
        })
    })
})
