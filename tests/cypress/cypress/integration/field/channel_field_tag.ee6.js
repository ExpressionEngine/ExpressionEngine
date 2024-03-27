/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import Publish from '../../elements/pages/publish/Publish';

const createField = new CreateField;
const publish = new Publish;
const channel_field_form = new ChannelFieldForm;

context('channel:field tag', () => {

    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.auth()

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.visit('admin.php?/cp/design')
        })
    })

    it('Field using Value/Label Pairs', () => {
        cy.visit('admin.php?/cp/fields')
        cy.dismissLicenseAlert()
        channel_field_form.createField({
            group_id: 1,
            type: 'Checkboxes',
            label: 'Checkboxes Field'
        })
        cy.get('div').contains('Checkboxes Field').click()
        cy.get('div.checkbox-label__text').contains('Value/Label Pairs').click()
        cy.get('a').contains('Add New').click()
        cy.get('input[name = "value_label_pairs[rows][new_row_1][value]"]').type('1')
        cy.get('input[name = "value_label_pairs[rows][new_row_1][label]"]').type('one')
        cy.get('a').contains('Add A Row').click()
        cy.get('input[name = "value_label_pairs[rows][new_row_2][value]"]').type('2')
        cy.get('input[name = "value_label_pairs[rows][new_row_2][label]"]').type('two')
        cy.get('a').contains('Add A Row').click()
        cy.get('input[name = "value_label_pairs[rows][new_row_3][value]"]').type('3')
        cy.get('input[name = "value_label_pairs[rows][new_row_3][label]"]').type('three')
        cy.get('input[type=hidden][name=field_id]').invoke('val').then((field_id) => {
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('/index.php/fields/properties/' + field_id)
            cy.hasNoErrors()
            cy.get('.field_id').invoke('text').should('eq', field_id)
            cy.get('.field_name').invoke('text').should('eq', 'checkboxes_field')
            cy.get('.field_label').invoke('text').should('eq', 'Checkboxes Field')
            cy.get('.field_options p').eq(0).find('.value').invoke('text').should('eq', '1')
            cy.get('.field_options p').eq(0).find('.label').invoke('text').should('eq', 'one')
            cy.get('.field_options p').eq(1).find('.value').invoke('text').should('eq', '2')
            cy.get('.field_options p').eq(1).find('.label').invoke('text').should('eq', 'two')
            cy.get('.field_options p').eq(2).find('.value').invoke('text').should('eq', '3')
            cy.get('.field_options p').eq(2).find('.label').invoke('text').should('eq', 'three')
        })
        
        
    })

    it('Field populated manually', () => {
        cy.authVisit('admin.php?/cp/fields')
        cy.dismissLicenseAlert()
        channel_field_form.createField({
            group_id: 1,
            type: 'Select Dropdown',
            label: 'Select Dropdown'
        })
        cy.get('div').contains('Select Dropdown').click()
        cy.get('div.checkbox-label__text').contains('Populate manually').click()
        cy.get('textarea[name="field_list_items"]:visible').type('uno{enter}dos{enter}tres{enter}quatro')
        cy.get('input[type=hidden][name=field_id]').invoke('val').then((field_id) => {
            cy.get('body').type('{ctrl}', {release: false}).type('s')

            cy.visit('/index.php/fields/properties/' + field_id)
            cy.hasNoErrors()
            cy.get('.field_id').invoke('text').should('eq', field_id)
            cy.get('.field_name').invoke('text').should('eq', 'select_dropdown')
            cy.get('.field_label').invoke('text').should('eq', 'Select Dropdown')
            cy.get('.field_options p').eq(0).find('.value').invoke('text').should('eq', 'uno')
            cy.get('.field_options p').eq(0).find('.label').invoke('text').should('eq', 'uno')
            cy.get('.field_options p').eq(1).find('.value').invoke('text').should('eq', 'dos')
            cy.get('.field_options p').eq(1).find('.label').invoke('text').should('eq', 'dos')
            cy.get('.field_options p').eq(2).find('.value').invoke('text').should('eq', 'tres')
            cy.get('.field_options p').eq(2).find('.label').invoke('text').should('eq', 'tres')
        })

    })

    it('Textarea field', () => {
        cy.visit('/index.php/fields/properties/news_body')
        cy.hasNoErrors()
        cy.get('.field_name').invoke('text').should('eq', 'news_body')
        cy.get('.field_label').invoke('text').should('eq', 'Body')
        cy.get('.field_options').invoke('text').then((text) => {
            expect(text.trim()).to.be.empty
        })

    })

})
