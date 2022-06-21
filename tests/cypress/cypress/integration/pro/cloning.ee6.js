/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
import Publish from '../../elements/pages/publish/Publish';
import FluidField from '../../elements/pages/publish/FluidField';
const page = new AddonManager;
const publish = new Publish;
const fluid_field = new FluidField;

context('Entry Cloning', () => {

    before(function() {
      cy.intercept('**/check').as('check')
      cy.intercept('**/license/handleAccessResponse').as('license')
      
      cy.task('db:seed')
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.task('db:load', '../../support/sql/more-fields.sql')
      cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
      cy.eeConfig({ item: 'login_logo', value: '' })
      cy.eeConfig({ item: 'favicon', value: '' })
      cy.authVisit(page.url);
      page.get('first_party_addons').find('.add-on-card:contains("ExpressionEngine Pro") a').click()

      cy.wait('@check')
      cy.wait('@license')
      cy.get('.app-notice---error').should('not.exist')
    })


    beforeEach(function() {
        cy.auth();
    })

    after(function() {
      cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
      cy.eeConfig({ item: 'login_logo', value: '' })
      cy.eeConfig({ item: 'favicon', value: '' })
    })


    const available_fields = [
        "A Date",
        "Checkboxes",
        "Electronic-Mail Address",
        "Home Page",
        "Image",
        "Item",
        "Middle Class Text",
        "Multi Select",
        "Radio",
        "Selectable Buttons",
        "Selection",
        "Stupid Grid",
        "Text",
        "Truth or Dare?",
        "YouTube URL"
      ];


    it('clone entry with Fluid', () => {

        cy.visit(Cypress._.replace(publish.url, '{channel_id}', 3))

        publish.get('title').type("Fluid Field Test the First")
        publish.get('url_title').clear().type("fluid-field-test-first")

        fluid_field.get('actions_menu.fields').then(function($li) {
            let existing_fields = Cypress._.map($li, function(el) {
                return Cypress.$(el).text().replace('Add ', '').trim();
            })

            expect(existing_fields).to.deep.equal(available_fields)
        })

        available_fields.forEach(function(field, index) {
            fluid_field.get('actions_menu.fields').eq(index).click()

            fluid_field.get('items').eq(index).find('label').contains(field)
        })

        publish.get('save').click()
        publish.get('alert').contains('Entry Created')

        // Make sure the fields stuck around after save
        cy.log('Make sure the fields stuck around after save')
        available_fields.forEach(function(field, index) {
            fluid_field.get('items').eq(index).find('label').contains(field)
            fluid_field.add_content(index)
        })

        publish.get('save').click()

        cy.screenshot({capture: 'fullPage'});

        publish.get('alert').contains('Entry Updated')
        cy.url().should('include', '/cp/publish/edit/entry/11')

        available_fields.forEach(function(field, index) {
            fluid_field.check_content(index)
        })

        cy.get('.saving-options').click()
        cy.get('[value=save_as_new_entry]').first().click()

        publish.get('alert').contains('Entry Created')
        cy.url().should('not.include', '/cp/publish/edit/entry/11')
        publish.get('title').invoke('val').should('eq', "Copy of Fluid Field Test the First")
        publish.get('url_title').invoke('val').should('not.eq', "fluid-field-test-first")
        available_fields.forEach(function(field, index) {
            fluid_field.check_content(index)
        })

    })

    it('clones the entry with regular fields', () => {
        cy.visit('admin.php?/cp/channels/edit/1')
        cy.get('.tab-bar__tab:contains("Fields")').click()
        cy.get('[data-input-value=custom_fields] .ctrl-all input[type=checkbox]').check();
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        var skew = 0;
        
        cy.visit('admin.php?/cp/publish/edit/entry/2')
        cy.get('button:contains("Relate Entry")').first().click()
        cy.get('a.dropdown__link:contains("Getting to Know ExpressionEngine")').first().click();
        cy.get('input[name=field_id_8][type=text][rel=date-picker]').type((9 + skew).toString() + '/14/2017 2:56 PM')
        cy.get('[name=title]').click() // Dismiss the date picker
        cy.get('[data-input-value=field_id_9] input[type=checkbox]').eq(0 + skew).check();
        cy.get('input[name=field_id_11]').clear().type('rspec-' + skew.toString() + '@example.com')
        cy.get('input[name=field_id_12]').clear().type('http://www.example.com/page/' + skew.toString())
        cy.get('.ck-content').type('Lorem ipsum dolor sit amet' + skew);
        cy.get('[data-input-value=field_id_15] input[type=checkbox]').eq(0 + skew).check()
        cy.get('input[type=radio][name=field_id_16]').eq(1 + skew).check()
        cy.get('[data-input-value=field_id_18]').click()
        cy.get('[data-input-value=field_id_18] .select__dropdown-items span:contains("Corndog")').click({force:true})
        cy.get('#field_id_19').find('a[rel="add_row"]').first().click()
        cy.get('#field_id_19 .grid-field__table tbody tr:visible input:visible').eq(0).clear().type('Lorem' + skew.toString())
        cy.get('#field_id_19 .grid-field__table tbody tr:visible input:visible').eq(1).clear().type('ipsum' + skew.toString())
        cy.get('[data-toggle-for="field_id_21"].toggle-btn').click()

        

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('.app-notice---success').contains('Entry Updated');
        
        cy.get('.saving-options').click()
        cy.get('[value=save_as_new_entry]').first().click()

        publish.get('alert').contains('Entry Created')
        cy.url().should('not.include', '/cp/publish/edit/entry/2')

        cy.get('[name=title]').invoke('val').should('eq', "Copy of Welcome to the Example Site!")
        cy.get('[name=url_title]').invoke('val').should('not.eq', "welcome_to_the_example_site")
        cy.get('input[name=field_id_8][type=text]').invoke('val').should('eq', (9 + skew).toString() + '/14/2017 2:56 PM')
        cy.get('[data-input-value=field_id_9] input[type=checkbox]').eq(0 + skew).should('be.checked');
        cy.get('input[name=field_id_11]').invoke('val').should('eq', 'rspec-' + skew.toString() + '@example.com')
        cy.get('input[name=field_id_12]').invoke('val').should('eq', 'http://www.example.com/page/' + skew.toString())
        cy.get('.ck-content').contains('Lorem ipsum dolor sit amet' + skew);
        cy.get('[data-input-value=field_id_15] input[type=checkbox]').eq(0 + skew).should('be.checked');
        cy.get('input[type=radio][name=field_id_16]').eq(1 + skew).should('be.checked');
        cy.get('input[type=hidden][name=field_id_18]').invoke('val').should('eq', 'Corndog')
        cy.get('#field_id_19 .grid-field__table tbody tr:visible input:visible').eq(0).invoke('val').should('eq', 'Lorem' + skew.toString())
        cy.get('#field_id_19 .grid-field__table tbody tr:visible input:visible').eq(1).invoke('val').should('eq', 'ipsum' + skew.toString())
        cy.get('[data-toggle-for="field_id_21"].toggle-btn').should('have.class', 'on')

        cy.get('.tab-bar__tab:contains("Categories")').click()
        cy.get('input[type=checkbox][value=1]:visible').should('be.checked')
        cy.get('input[type=checkbox][value=2]:visible').should('not.be.checked')

    })

    it('clones the entry and creates Pages record', () => {
        cy.visit(page.url);
        page.get('first_party_addons').find('.add-on-card:contains("Pages") a').click()
        
        cy.visit('admin.php?/cp/publish/edit/entry/2')
        cy.get('.tab-bar__tab:contains("Pages")').click()
        cy.get('input[name=pages__pages_uri]').type('getting_to_know_expressionengine');
        cy.get('[data-input-value="pages__pages_template_id"] .js-dropdown-toggle').click()
        cy.get('[data-input-value="pages__pages_template_id"] .select__dropdown-item:contains("comments")').first().click();

        cy.get('body').type('{ctrl}', {release: false}).type('s')
        cy.get('.app-notice---success').contains('Entry Updated');

        cy.get('.tab-bar__tab:contains("Pages")').click()
        cy.get('input[name=pages__pages_uri]').invoke('val').should('eq', '/getting_to_know_expressionengine');
        cy.get('[data-input-value="pages__pages_template_id"] .select__button-label').invoke('text').should('include', 'comments');

        cy.get('.saving-options').click()
        cy.get('[value=save_as_new_entry]').first().click()

        publish.get('alert').contains('Entry Created')
        cy.get('.tab-bar__tab:contains("Pages")').click()
        cy.get('input[name=pages__pages_uri]').invoke('val').should('not.eq', '/getting_to_know_expressionengine');
        cy.get('[data-input-value="pages__pages_template_id"] .select__button-label').invoke('text').should('include', 'comments');

    })



})
