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

    it('displays', function() {
        page.get('variables').its('length').should('eq', 14)
    })

    it('can filter by keyword', function() {
        page.get('keyword_search').type('html').type('{enter}')

        cy.hasNoErrors()

        page.get('variables').its('length').should('eq', 4) // This also searches data...
    })

    it('can find templates that use a variable', function() {
        page.get('variables').eq(6).find('.toolbar .find a').click()

        cy.hasNoErrors()

        page.get('page_title').contains('Search Results')
        page.get('page_title').contains('{html_head}')
        page.get('variables').its('length').should('eq', 9) // Yeah, not technically 'variables' but the selectors work
    })

    it('can navigate to edit form', function() {
        page.get('variables').eq(6).find('.toolbar .edit a').click()

        cy.hasNoErrors()
    })

    it('should validate the form', function() {
        page.get('create_new_button').click()

        form.get('name').clear().type('lots of neat stuff').trigger('blur')

        page.hasError(form.get('name'), 'The name you submitted may only contain alpha-numeric characters, underscores, and dashes')
        page.hasErrors()
    })

    it('can create a new variable', function() {
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
        cy.get('input[value="Save Template Variable"]').first().click()

        cy.hasNoErrors()

        page.hasAlert()
        page.get('alert').contains('Template Variable Created')
        page.get('alert').contains('rspec-test')
    })

    it('can remove a variable', function() {
        page.get('variables').its('length').then((length) => {
            page.get('variables').eq(0).find('td:nth-child(4) input').click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Remove')
            page.get('action_submit_button').click()

            page.get('modal_submit_button').click()

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('variables').its('length').should('eq', length-1)
        })
    })
})
