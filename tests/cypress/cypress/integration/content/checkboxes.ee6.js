/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import Publish from '../../elements/pages/publish/Publish';

const createField = new CreateField;
const publish = new Publish;
const channel_field_form = new ChannelFieldForm;

context('Checkboxes field tags', () => {

    before(function () {
        cy.task('db:seed')
        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.auth()

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.visit('admin.php?/cp/design')
            cy.visit('admin.php?/cp/fields/create/1')
            channel_field_form.createField({
                group_id: 1,
                type: 'Checkboxes',
                label: 'Checkboxes Field'
            })
        })
    })

    afterEach(function () {
        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('fieldset:contains("Checkboxes Field")').find('input[type="checkbox"]:checked').each(function(el, i){
            cy.get(el).uncheck()
        })
        cy.get('body').type('{ctrl}', {release: false}).type('s')
    })

    it('Field using Value/Label Pairs', () => {
        cy.visit('admin.php?/cp/fields')
        cy.dismissLicenseAlert()
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
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('fieldset:contains("Checkboxes Field")').find('input[type="checkbox"]').should('have.length', 3)
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("one")').click()
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("three")').click()
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('/index.php/entries/checkboxes')
        cy.hasNoErrors()
        cy.get('.checkboxes .row').eq(0).find('.item').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(0).find('.label').invoke('text').should('eq', 'one')
        cy.get('.checkboxes .row').eq(0).find('.value').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(0).find('.count').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(0).find('.index').invoke('text').should('eq', '0')
        cy.get('.checkboxes .row').eq(0).find('.total').invoke('text').should('eq', '2')

        cy.get('.checkboxes .row').eq(1).find('.item').invoke('text').should('eq', '3')
        cy.get('.checkboxes .row').eq(1).find('.label').invoke('text').should('eq', 'three')
        cy.get('.checkboxes .row').eq(1).find('.value').invoke('text').should('eq', '3')
        cy.get('.checkboxes .row').eq(1).find('.count').invoke('text').should('eq', '2')
        cy.get('.checkboxes .row').eq(1).find('.index').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(1).find('.total').invoke('text').should('eq', '2')

    })

    it('Field populated manually', () => {
        cy.authVisit('admin.php?/cp/fields')
        cy.dismissLicenseAlert()
        cy.get('div').contains('Checkboxes Field').click()
        cy.get('div.checkbox-label__text').contains('Populate manually').click()
        cy.get('textarea[name="field_list_items"]:visible').type('uno{enter}dos{enter}tres{enter}quatro')
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("dos")').click()
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("quatro")').click()
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('/index.php/entries/checkboxes')
        cy.hasNoErrors()
        cy.get('.checkboxes .row').eq(0).find('.item').invoke('text').should('eq', 'dos')
        cy.get('.checkboxes .row').eq(0).find('.label').invoke('text').should('eq', 'dos')
        cy.get('.checkboxes .row').eq(0).find('.value').invoke('text').should('eq', 'dos')
        cy.get('.checkboxes .row').eq(0).find('.count').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(0).find('.index').invoke('text').should('eq', '0')
        cy.get('.checkboxes .row').eq(0).find('.total').invoke('text').should('eq', '2')

        cy.get('.checkboxes .row').eq(1).find('.item').invoke('text').should('eq', 'quatro')
        cy.get('.checkboxes .row').eq(1).find('.label').invoke('text').should('eq', 'quatro')
        cy.get('.checkboxes .row').eq(1).find('.value').invoke('text').should('eq', 'quatro')
        cy.get('.checkboxes .row').eq(1).find('.count').invoke('text').should('eq', '2')
        cy.get('.checkboxes .row').eq(1).find('.index').invoke('text').should('eq', '1')
        cy.get('.checkboxes .row').eq(1).find('.total').invoke('text').should('eq', '2')

    })

})
