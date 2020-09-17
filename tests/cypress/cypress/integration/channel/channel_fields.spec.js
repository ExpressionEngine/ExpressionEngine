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

    it('has seven fields', function() {
        page.get('fields').its('length').should('eq', 7)
        page.get('fields_edit').its('length').should('eq', 7)
        page.get('fields_checkboxes').its('length').should('eq', 7)
    })

    describe('when creating or editing fields', function() {
        it('creates a field', function() {
            form.createField({
                type: 'Text Input',
                label: 'Shipping Method'
            })

            page.hasAlert('success')
        })

        it('saves a field', function() {
            page.get('fields_edit').eq(1).click()
            form.get('form').find('.btn[value="save"]').first().click()

            page.hasAlert('success')
        })

        it('invalidates reserved words used in field_name', function() {
            form.createField({
                type: 'Date',
                label: 'Date'
            })

            page.get('alert').contains('Cannot Create Field')
        })
    })

    it('deletes a field', function() {
        page.get('fields_checkboxes').eq(1).click()

        page.get('bulk_action').should('exist')
        page.get('action_submit_button').should('exist')

        page.get('bulk_action').select('Remove')
        page.get('action_submit_button').click()

        page.get('modal_submit_button').click()

        page.hasAlert('success')
        page.get('fields').its('length').should('eq', 6)
    })
})