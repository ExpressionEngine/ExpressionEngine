/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
import ChannelFieldForm from '../../elements/pages/channel/ChannelFieldForm';
import Publish from '../../elements/pages/publish/Publish';

const createField = new CreateField;
const publish = new Publish;
const channel_field_form = new ChannelFieldForm;

context('Editing field settings', () => {

    before(function () {
        cy.task('db:seed')
        cy.auth()
        cy.visit('admin.php?/cp/fields/create/1')
        channel_field_form.createField({
            group_id: 1,
            type: 'Checkboxes',
            label: 'Checkboxes Field'
        })
    })

    it('Checkboxes field options can be changed', () => {
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
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("one")').parent().find('input[type=checkbox]').should('be.checked')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("three")').parent().find('input[type=checkbox]').should('be.checked')

        cy.visit('admin.php?/cp/fields')
        cy.get('div').contains('Checkboxes Field').click()
        cy.get('div.checkbox-label__text').contains('Populate manually').click()
        cy.get('textarea[name="field_list_items"]:visible').type('uno{enter}dos{enter}tres{enter}quatro')
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        cy.visit('admin.php?/cp/publish/edit/entry/1')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("1")').parent().find('input[type=checkbox]').should('be.checked')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("3")').parent().find('input[type=checkbox]').should('be.checked')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("3")').click()
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("dos")').click()
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("quatro")').click()
        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("1")').parent().find('input[type=checkbox]').should('be.checked')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("3")').should('not.exist')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("dos")').parent().find('input[type=checkbox]').should('be.checked')
        cy.get('fieldset:contains("Checkboxes Field")').find('.checkbox-label__text:contains("quatro")').parent().find('input[type=checkbox]').should('be.checked')
    })

})
