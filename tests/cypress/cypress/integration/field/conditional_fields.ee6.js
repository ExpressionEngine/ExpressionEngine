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
    })

    after(function() {

    })

    it('can be used in a textarea field', () => {
        // Setup conditional on field
        cy.authVisit('admin.php?/cp/fields')
        cy.get('.list-item').contains('{news_body}').closest('.list-item').click();
        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Extended text').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is not').click();

        cy.get('.condition-rule-value-wrap:visible input').type('hide');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('input[name="title"]').type('CF textarea test');
        cy.get('textarea[name="field_id_2"]').type('show', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is shown on entry page after save');
        cy.get('textarea[name="field_id_1"]').should('be.visible');
        // Edit entry to not conditionally hide the field
        cy.get('textarea[name="field_id_2"]').clear({ force: true }).type('hide', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is not shown in the template



        // Assert field is not shown on entry page after save
        cy.get('textarea[name="field_id_1"]').should('not.be.visible');

        // Assert field shows up
    })

    it('can be used in a file field', function() {
        // Setup conditional on field
        cy.authVisit('admin.php?/cp/fields')
        cy.get('.list-item').contains('{news_image}').closest('.list-item').click();
        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('input[name="title"]').type('CF file test');
        cy.get('textarea[name="field_id_1"]').type('hide', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is shown on entry page after save');
        cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('not.be.visible');
        // Edit entry to not conditionally hide the field
        cy.get('textarea[name="field_id_1"]').clear({ force: true }).type('show', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is not shown in the template



        // Assert field is not shown on entry page after save
        cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible');

        // Assert field shows up
    })

    it('can be used in a grid field', function() {
        // Create grid field
        cy.authVisit('admin.php?/cp/fields')
        cy.get('.button--primary').contains('New Field').click();
        cy.get('#fieldset-field_type .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains(/^Grid$/).click({ force: true });
        cy.get('input[name="field_label"]').type('CF Grid');
        cy.get('.fields-grid-setup a[rel="add_new"]:visible').click();
        cy.get('input[name="grid[cols][new_1][col_label]"]').type('Column');
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

    })

    it('can be used in a relationship field', function() {
        // Setup conditional on field
        cy.authVisit('admin.php?/cp/fields')
        cy.get('.list-item').contains('{related_news}').closest('.list-item').click();
        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('input[name="title"]').type('CF relationship test');
        cy.get('textarea[name="field_id_1"]').type('hide', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is shown on entry page after save');
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('div[data-relationship-react]').should('not.be.visible');
        // Edit entry to not conditionally hide the field
        cy.get('textarea[name="field_id_1"]').clear({ force: true }).type('show', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is not shown in the template



        // Assert field is not shown on entry page after save
        cy.get('input[name="field_id_8[data][]"]').closest('.field-control').find('div[data-relationship-react]').should('be.visible');

        // Assert field shows up
    })

    it('can be used in a fluid field', function() {
        // Create fluid field in News Group
        cy.authVisit('admin.php?/cp/fields&group_id=1')
        cy.get('.button--primary').contains('New Field').click();
        cy.get('#fieldset-field_type .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Fluid').click({ force: true });
        cy.get('input[name="field_label"]').type('CF Fluid');
        cy.get('#fieldset-field_channel_fields input[type="checkbox"][value="1"]').click();

        cy.get('#fieldset-field_is_conditional button').click();

        cy.get('.condition-rule-field:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('Body').click();

        cy.get('.condition-rule-operator-wrap:visible .select__button').click();
        cy.get('.dropdown--open .select__dropdown-item').contains('is').click();

        cy.get('.condition-rule-value-wrap:visible input').type('show');

        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Create entry with conditionally hidden field
        cy.get('.ee-sidebar__items-section').contains('Entries').trigger('mouseover');
        cy.get('.dropdown__item').contains('News').closest('.dropdown__item').find('.fa-plus').click();

        cy.log('Edit entry to conditionally hide the field');
        cy.get('input[name="title"]').type('CF fluid test');
        cy.get('textarea[name="field_id_1"]').type('hide', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        cy.log('Assert field is shown on entry page after save');
        cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('not.be.visible');
        // Edit entry to not conditionally hide the field
        cy.get('textarea[name="field_id_1"]').clear({ force: true }).type('show', { force: true });
        cy.get('button[data-submit-text="Save"]:eq(0)').click();

        // Assert field is not shown in the template



        // Assert field is not shown on entry page after save
        cy.get('input[name="field_id_3"]').parent('.field-control').find('div[data-file-field-react]').should('be.visible');

    })

    context('different combinations of rules', function() {

    })

})