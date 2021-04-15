/// <reference types="Cypress" />

import Category from '../../elements/pages/channel/Category';
const page = new Category;
const { _, $ } = Cypress

context('Categories', () => {

    before(function() {
        cy.task('db:seed')

        //copy templates
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('create custom category fields', function() {
        cy.get('.sidebar__link a').contains('News Categories').parent().find('a.edit').click({force: true})
        cy.get('.tab-bar__tab').contains('Fields').click()

        cy.get('.button').contains('Add Field').click();
        cy.get('.app-modal__content').should('be.visible');
        cy.get('input[name=field_label]').type('custom textfield').blur()
        cy.get('.app-modal__content .button--primary').first().click()
        cy.get('.app-modal__content').should('not.be.visible');

        cy.get('.button').contains('Add Field').click();
        cy.get('.app-modal__content').should('be.visible');
        cy.get('[data-input-value=field_type] .select__button').click()
        cy.get('[data-input-value=field_type] .select__dropdown .select__dropdown-item').contains('Textarea').click()
        cy.get('input[name=field_label]').type('custom textarea').blur()
        cy.get('.app-modal__content .button--primary').first().click()
        cy.get('.app-modal__content').should('not.be.visible');

        cy.get('.button').contains('Add Field').click();
        cy.get('.app-modal__content').should('be.visible');
        cy.get('[data-input-value=field_type] .select__button').click()
        cy.get('[data-input-value=field_type] .select__dropdown .select__dropdown-item').contains('Select Dropdown').click()
        cy.get('input[name=field_label]').type('custom dropdown').blur()
        cy.get('[name=field_pre_populate][value=n]').check();
        cy.get('[name=field_list_items]').type('option one{enter}option two{enter}option three')
        cy.get('.app-modal__content .button--primary').first().click()
        cy.get('.app-modal__content').should('not.be.visible');

        cy.get('#fieldset-category_fields').contains('custom textfield')
        cy.get('#fieldset-category_fields').contains('custom textarea')
        cy.get('#fieldset-category_fields').contains('custom dropdown')

        cy.get('.title-bar__extra-tools .button--primary').first().click()

    })

    it('add category with all fields', function() {
        cy.get('.button--primary').contains('New Category').click();

        cy.get('input[name=cat_name]').type('category one')
        cy.get('textarea[name=cat_description]').type('one description')

        cy.get('input[name=field_id_1]').type('one textfield')
        cy.get('textarea[name=field_id_2]').type('one textarea')
        cy.get('[data-input-value=field_id_3] .select__button').click()
        cy.get('[data-input-value=field_id_3] .select__dropdown .select__dropdown-item').contains('option two').click()

        cy.get('button').contains('Upload New').click()
        cy.get('.dropdown--open .dropdown__link').contains('Main Upload Directory').click()

        cy.get('input[name="file"]').attachFile('../../support/file/programming.gif')
        cy.get('.button[value=Upload File]').first().click()

        cy.get('.js-nestable-categories').contains('category one')
    })

    it('add category from entry page', function() {
        cy.visit('admin.php?/cp/publish/edit/entry/1');
        
        cy.get('.tab-bar__tab').contains('Categories').click();

        cy.get('input[name=cat_name]').type('category one')
        cy.get('textarea[name=cat_description]').type('one description')

        cy.get('input[name=field_id_1]').type('one textfield')
        cy.get('textarea[name=field_id_2]').type('one textarea')
        cy.get('[data-input-value=field_id_3] .select__button').click()
        cy.get('[data-input-value=field_id_3] .select__dropdown .select__dropdown-item').contains('option two').click()

        cy.get('button').contains('Upload New').click()
        cy.get('.dropdown--open .dropdown__link').contains('Main Upload Directory').click()

        cy.get('input[name="file"]').attachFile('../../support/file/programming.gif')
        cy.get('.button[value=Upload File]').first().click()

        cy.get('.js-nestable-categories').contains('category one')
    })


})
