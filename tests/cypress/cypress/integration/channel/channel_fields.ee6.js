/// <reference types="Cypress" />

import ChannelFields from '../../elements/pages/channel/ChannelFields';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
const page = new ChannelFields;
const form = new ChannelFieldForm;
const { _, $ } = Cypress

context('Channel Fields', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    describe('Creating & editing fields', function() {
        it('Creates new text field', function() {
            cy.visit('admin.php?/cp/fields/create/1')
            form.createField({
                type: 'Text Input',
                label: 'Shipping Method'
            })

            page.hasAlert('success')
        })

        it('Saves existing field without changes', function() {
            page.get('fields_edit').eq(1).click()
            //form.get('form').find('.button[value="save"]').first().click()
            cy.get('button[value="save"]').eq(0).click()
            page.hasAlert('success')
        })

        it('Cannot use reserved words in field_name', function() {
            cy.visit('admin.php?/cp/fields/create/1')
            form.createField({
                type: 'Date',
                label: 'Date'
            })

            page.get('alert').contains('Cannot Create Field')
        })
    })

    it('Delete a field', function() {
        page.get('fields').its('length').then((length) => {
        
            page.get('fields_checkboxes').eq(1).click()

            page.get('bulk_action').should('exist')
            page.get('action_submit_button').should('exist')

            page.get('bulk_action').select('Delete')
            //page.get('action_submit_button').click()
            cy.get('button[value="submit"]').first().click()
            cy.wait(400)// AJ
            //page.get('new_modal_submit_button').click()
            cy.get('[value="Confirm and Delete"]').filter(':visible').first().click()

            page.hasAlert('success')
            page.get('fields').its('length').should('eq', length-1)
        })
    })
})
