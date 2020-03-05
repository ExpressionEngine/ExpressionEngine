/// <reference types="Cypress" />

import ChannelFields from '../../elements/pages/channel/ChannelFields';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
const page = new ChannelFieldForm;
const list = new ChannelFields;
const { _, $ } = Cypress

context('Fluid Fields', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(list.url);
    })

    it('creates a fluid field', function() {
        cy.visit(page.url)
        page.get('field_type').contains('Fluid').click({ force: true })
        page.get('field_label').clear().type('Fluid Field Body')
        page.get('field_name').clear().type('fluid_field_body')

        // page.get('fields').find("[value='1']").click()
        page.get('fields').find("[value='2']").click()
        page.get('fields').find("[value='3']").click()
        page.get('fields').find("[value='5']").click()
        page.get('fields').find("[value='6']").click()
        page.get('fields').find("[value='7']").click()
        page.submit()

        page.get('alert').contains('The field Fluid Field Body has been')

        cy.visit(list.url).then(function() {
            list.get('fields').contains('Fluid Field Body')
            list.get('fields').contains('{fluid_field_body}')

            page.load_edit_for_custom_field('Fluid Field Body')

            page.get('field_type_input').should('have.value', 'fluid_field')
            page.get('field_label').should('have.value', 'Fluid Field Body')
            page.get('field_name').should('have.value', 'fluid_field_body')
            page.get('fields').find("[value='1']").should('not.be.checked')
            page.get('fields').find("[value='2']").should('be.checked')
            page.get('fields').find("[value='3']").should('be.checked')
            page.get('fields').find("[value='4']").should('not.be.checked')
            page.get('fields').find("[value='5']").should('be.checked')
            page.get('fields').find("[value='6']").should('be.checked')
            page.get('fields').find("[value='7']").should('be.checked')

        })
    })


    it('can add a new field to the fluid field', function() {

        // page.get('field_type').contains('Fluid').click({ force: true })
        // page.get('field_label').clear().type('Fluid Field Body')
        // page.get('field_name').clear().type('fluid_field_body')
        // page.get('fields').find("[value='2']").click()
        // page.get('fields').find("[value='3']").click()
        // page.get('fields').find("[value='5']").click()
        // page.get('fields').find("[value='6']").click()
        // page.get('fields').find("[value='7']").click()
        // page.submit()

        // list.get('alert').contains('The field Fluid Field Body has been')
        cy.get('.tbl-row .main a').contains('Fluid Field Body').click()
            // confirm our state
        page.get('fields').find("[value='1']").should('not.be.checked')
        page.get('fields').find("[value='2']").should('be.checked')
        page.get('fields').find("[value='3']").should('be.checked')
        page.get('fields').find("[value='4']").should('not.be.checked')
        page.get('fields').find("[value='5']").should('be.checked')
        page.get('fields').find("[value='6']").should('be.checked')
        page.get('fields').find("[value='7']").should('be.checked')

        page.get('fields').find("[value='1']").click()
        page.submit()

        page.get('fields').find("[value='1']").should('be.checked')
        page.get('fields').find("[value='2']").should('be.checked')
        page.get('fields').find("[value='3']").should('be.checked')
        page.get('fields').find("[value='4']").should('not.be.checked')
        page.get('fields').find("[value='5']").should('be.checked')
        page.get('fields').find("[value='6']").should('be.checked')
        page.get('fields').find("[value='7']").should('be.checked')
    })

    it('can remove a field from the fluid field', function() {
        // page.get('fields').find("[value='1']").click()
        // page.get('fields').find("[value='2']").click()
        // page.get('fields').find("[value='3']").click()
        // page.get('fields').find("[value='5']").click()
        // page.get('fields').find("[value='6']").click()
        // page.get('fields').find("[value='7']").click()
        // page.submit()

        // list.get('alert').contains('The field Fluid Field Body has been')

        cy.get('.tbl-row .main a').contains('Fluid Field Body').click()
            // confirm our state
        page.get('fields').find("[value='1']").should('be.checked')
        page.get('fields').find("[value='2']").should('be.checked')
        page.get('fields').find("[value='3']").should('be.checked')
        page.get('fields').find("[value='4']").should('not.be.checked')
        page.get('fields').find("[value='5']").should('be.checked')
        page.get('fields').find("[value='6']").should('be.checked')
        page.get('fields').find("[value='7']").should('be.checked')

        page.get('fields').find("[value='2']").filter(':visible').click()
        page.submit()

        page.get('modal_submit_button').click()

        page.get('fields').find("[value='1']").should('be.checked')
        page.get('fields').find("[value='2']").should('not.be.checked')
        page.get('fields').find("[value='3']").should('be.checked')
        page.get('fields').find("[value='4']").should('not.be.checked')
        page.get('fields').find("[value='5']").should('be.checked')
        page.get('fields').find("[value='6']").should('be.checked')
        page.get('fields').find("[value='7']").should('be.checked')
    })

    it('deletes a fluid field', function() {

        list.get('fields_checkboxes').eq(7).click()

        list.get('bulk_action').should('exist')
        list.get('action_submit_button').should('exist')

        list.get('bulk_action').select('Remove')
        list.get('action_submit_button').click()

        list.get('modal_submit_button').click()

        list.get('fields').eq(0).contains('Fluid Field Body').should('not.exist')
    })

})