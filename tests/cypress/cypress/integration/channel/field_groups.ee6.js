/// <reference types="Cypress" />

import FieldGroups from '../../elements/pages/channel/FieldGroups';
const page = new FieldGroups;
const { _, $ } = Cypress

context('Field Groups', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('has two field groups', function() {
        page.get('field_groups').its('length').should('eq', 2)
    })


    it('creates a field group', function() {
        page.get('create_new').click()


        page.save_field_group('Test Group 1')
        cy.get('b').contains('Test Group 1')
        cy.get('p').contains('has been created')



        page.get('field_groups').its('length').should('eq', 3)
        page.get('field_groups_edit').its('length').should('eq', 3)
        page.get('field_groups_fields').its('length').should('eq', 3)

        page.get('field_groups').contains('Test Group 1')
    })

    it('saves the field group name', function() {
        page.get('field_groups_edit').eq(0).click({force: true})
        page.change('Test Group 2')

        cy.visit(page.url)

        page.get('field_groups').its('length').should('eq', 3)
        page.get('field_groups_edit').its('length').should('eq', 3)
        page.get('field_groups_fields').its('length').should('eq', 3)
    })


    it('deletes a field group', function() {
        page.get('field_groups').its('length').then((length) => {

            page.get('field_groups').eq(0).find('a[rel="modal-confirm-field_groups"]').first().click({force: true})//AJ


            //page.get('modal_submit_button').click()
            cy.get('input[value="Confirm and Delete"]').filter(':visible').first().click()
            page.hasAlert('success')
            //cy.hasNoErrors()

             //cy.authVisit(page.url);


            page.get('field_groups').its('length').should('eq', length-1)
        })
    })
})