/// <reference types="Cypress" />

import CreateField from '../../elements/pages/field/CreateField';
const page = new CreateField;
const { _, $ } = Cypress

context('Conditional Fields', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' });
        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' })
        cy.authVisit('admin.php?/cp/fields')
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/modifiers.group')
        cy.task('filesystem:delete', '../../system/user/config/stopwords.php')
    })

    it.only('legacy textarea field', () => {
        // Setup conditional on field
        cy.get('.list-item').contains('{news_body}').closest('.list-item').click();
        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is not').click();

        cy.get('.condition-rule-value-wrap:visible input').type('test');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();
        cy.get('.field-instruct').contains('Is Conditional: 1').should('not.contain', 'Conditionally Hidden: 1');

        // Edit entry to conditionally hide the field
        cy.get('input[name="title"]').type('Test');
        cy.get('textarea[name="field_id_2"]').type('test', {force: true});
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is hidden on entry page after save

        // Assert field is not shown in the template

        // Edit entry to not conditionally hide the field

        // Assert field shows up
    })

    it('file field', function() {
        cy.log('add new field')
    })

    it('grid field', function() {

    })

    it('relationship field', function() {

    })

    it('fluid field', function() {

    })

    context('different combinations of rules', function() {

    })

})