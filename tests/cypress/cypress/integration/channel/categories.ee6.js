/// <reference types="Cypress" />

import Category from '../../elements/pages/channel/Category';
const page = new Category;
const { _, $ } = Cypress

context('Categories', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })

        //add manipulation
        cy.authVisit('admin.php?/cp/files/uploads/edit/2');
        cy.contains('Add New Manipulation').click();
        cy.get('input[name="image_manipulations[rows][new_row_1][short_name]"]').type('smaller')
        cy.get('input[name="image_manipulations[rows][new_row_1][width]"]').type('100')
        cy.get('input[name="image_manipulations[rows][new_row_1][height]"]').type('100')
        cy.get('.title-bar__extra-tools .button--primary').first().click()
        cy.get('.icon--sync').click();
        cy.get('input[name="sizes[]"]').first().check();
        cy.contains('Sync Directory').first().click()

        /*cy.visit('admin.php?/cp/design/group/create')
        cy.get('input[name="group_name"]').eq(0).type('cats')
        cy.get('input[value="Save Template Group"]').eq(0).click()
        cy.get('p').contains('has been created')*/

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/cats.group')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
        cy.hasNoErrors()
    })

    afterEach(function() {
        cy.hasNoErrors()
    })

    it('create custom category fields', function() {
        cy.get('.sidebar__link a').contains('News Categories').first().parent().find('a.edit').click({force: true})
        cy.get('.tab-bar__tab').contains('Fields').click()

        cy.get('.button').contains('Add Field').click();
        cy.get('.app-modal__content').should('be.visible');
        cy.get('input[name=field_label]').should('be.visible');
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

        /*cy.get('button').contains('Upload New').click()
        cy.get('.dropdown--open .dropdown__link').contains('Main Upload Directory').click()

        cy.wait(5000);

        cy.get('.modal-file iframe').then(($iframe) => {
            const doc = $iframe.contents();
            //cy.wrap($iframe.find('input[name="file"]')).should('be.visible');
            console.log(doc);
            cy.log(doc);
            //cy.wrap(doc.find('input[name="file"]')).should('be.visible');
            cy.wrap(doc.find('input[name="file"]')).attachFile('../../support/file/programming.gif')
            cy.wrap(doc.find('.button.button--primary')).first().click()
        })*/

        cy.get('button').contains('Choose Existing').click()
        cy.get('.dropdown--open .dropdown__link').contains('About').click()

        cy.get('tr[data-id=7] td').first().click()
        cy.get('tr[data-id=7] td').should('not.be.visible')

        cy.get('.title-bar__extra-tools .button--primary').first().click()

        cy.visit(page.url);

        cy.get('.js-nestable-categories').contains('category one')
    })

    it('add category from entry page', function() {
        cy.visit('admin.php?/cp/publish/edit/entry/1');
        
        cy.get('.tab-bar__tab').contains('Categories').click();

        cy.contains('Add Category').click()

        cy.get('.app-modal--side').should('be.visible');

        cy.get('.app-modal--side input[name=cat_name]').type('category two')
        cy.get('.app-modal--side textarea[name=cat_description]').type('two description')

        cy.get('.app-modal--side input[name=field_id_1]').type('two textfield')
        cy.get('.app-modal--side textarea[name=field_id_2]').type('two textarea')
        cy.get('.app-modal--side [data-input-value=field_id_3] .select__button').click()
        cy.get('.app-modal--side [data-input-value=field_id_3] .select__dropdown .select__dropdown-item').contains('option three').click()

        cy.get('.app-modal--side .title-bar__extra-tools .button--primary').first().click()

        cy.get('.app-modal--side').should('not.be.visible');

        cy.wait(5000)
        cy.get('.checkbox-label__text:contains("category one")').parent().find('input[type=checkbox]').check()

        cy.get('.tab-bar__right-buttons .button--primary').first().click()

        cy.visit(page.url);

        cy.get('.js-nestable-categories').contains('category two')
    })

    it('check categories list on frontend', function() {
    
        cy.visit('index.php/cats/index')

        check_category_one()
        check_category_two()
    })

    it('check categories on entry page on frontend', function() {
    
        cy.visit('index.php/cats/entry')

        check_category_one()
        check_category_two()
    })

    it('check category heading on frontend', function() {
    
        cy.visit('index.php/cats/heading/category/C5')

        check_category_one()

        cy.visit('admin.php?/cp/settings/urls');

        cy.get('input[name=use_category_name][value=y]').check()

        cy.get('.title-bar__extra-tools .button--primary').first().click()

        cy.visit('index.php/cats/heading/category/category-one')

        check_category_one()
    })

    it('check category archive on frontend', function() {
    
        cy.visit('index.php/cats/archive')

        check_category_one()
        check_category_two()

    })

    it('check sorted category archive on frontend', function() {
    
        cy.visit('index.php/cats/archive-sorted')

        cy.get(".default-linear div").first().invoke('text').then((text) => {
            expect(text).equal('Site Info')
        })
        cy.get(".default-nested div").first().invoke('text').then((text) => {
            expect(text).equal('Site Info')
        })
        cy.get(".title-linear div").first().invoke('text').then((text) => {
            expect(text).equal('Site Info')
        })
        cy.get(".title-nested div").first().invoke('text').then((text) => {
            expect(text).equal('Site Info')
        })
        cy.get(".most_used_categories-linear div").first().invoke('text').then((text) => {
            expect(text).equal('Staff Bios')
        })
        cy.get(".most_used_categories-nested div").first().invoke('text').then((text) => {
            expect(text).equal('Staff Bios')
        })
    })

    function check_category_one() {
        cy.get('#category-one .category_name').invoke('text').then((text) => {
            expect(text).equal('category one')
        })
        cy.get('#category-one .category_description').invoke('text').then((text) => {
            expect(text).equal('one description')
        })
        cy.get('#category-one .custom_textfield').invoke('text').then((text) => {
            expect(text).equal('one textfield')
        })
        cy.get('#category-one .custom_textarea').invoke('text').then((text) => {
            expect(text).equal('one textarea')
        })
        cy.get('#category-one .custom_dropdown').invoke('text').then((text) => {
            expect(text).equal('option two')
        })
        
        cy.get('#category-one .category_image img')
            .should('be.visible')
            .and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
        })

        cy.get('#category-one .category_image_smaller img')
            .should('be.visible')
            .and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
        })
    }

    function check_category_two() {

        cy.get('#category-two .category_name').invoke('text').then((text) => {
            expect(text).equal('category two')
        })
        cy.get('#category-two .category_description').invoke('text').then((text) => {
            expect(text).equal('two description')
        })
        cy.get('#category-two .custom_textfield').invoke('text').then((text) => {
            expect(text).equal('two textfield')
        })
        cy.get('#category-two .custom_textarea').invoke('text').then((text) => {
            expect(text).equal('two textarea')
        })
        cy.get('#category-two .custom_dropdown').invoke('text').then((text) => {
            expect(text).equal('option three')
        })
    }

})
