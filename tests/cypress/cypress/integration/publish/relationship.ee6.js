/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';
import FluidField from '../../elements/pages/publish/FluidField';

const page = new Edit;
const fluid_field = new FluidField;

context('Relationship field - Edit', () => {
	before(function(){
    	cy.task('db:seed')
  	})

	beforeEach(function(){
		cy.auth();
		cy.hasNoErrors()
	})

	it('shows a 404 with no given entry_id', () => {
	    page.load()
	    cy.contains("404")
  	})

  	context('relationship field', () => {

	    // default, display_entry_id is Off, items haven't been added 
	    it('saves relationship field', () => {
	      cy.visit('admin.php?/cp/publish/edit/entry/1')
	      cy.get('button:contains("Relate Entry")').first().click()
	      cy.get('a.dropdown__link:contains("Welcome to the Example Site!")').first().click();
	      cy.get('a.dropdown__link:contains("Band Title")').first().click();
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.get('.app-notice---success').contains('Entry Updated')
	      cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
	      cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
	      cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
	      //cy.get('button:contains("Relate Entry")').should('not.be.visible')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	    })

	    //  display_entry_id On, items have been added
	    it('saves relationship field with display entry id', () => {
	      cy.visit('admin.php?/cp/fields/edit/8');
	      cy.get('[data-toggle-for="relationship_display_entry_id"]').click()
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.visit('admin.php?/cp/publish/edit/entry/1')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').invoke('val').then((val) => { expect(val).to.not.be.equal(" #2 / ") })
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.get('.app-notice---success').contains('Entry Updated');
	      cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
	      cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
	      cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
	      //cy.get('button:contains("Relate Entry")').should('not.be.visible')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	    })

	    //  display_entry_id Off, items have been added
	    it('saves relationship field without display entry id', () => {
	      cy.visit('admin.php?/cp/fields/edit/8');
	      cy.get('[data-toggle-for="relationship_display_entry_id"]').click()
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.visit('admin.php?/cp/publish/edit/entry/1')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.get('.app-notice---success').contains('Entry Updated');
	      cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
	      cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
	      cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
	      //cy.get('button:contains("Relate Entry")').should('not.be.visible')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	    })
  	})

	context('when using fluid fields', () => {
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
			"Selection",
			"Stupid Grid",
			"Text",
			"Truth or Dare?",
			"YouTube URL"
		];

		before(function(){
			cy.task('db:load', '../../channel_sets/channel-with-fluid-field.sql')
		})

		it('create new grid field', () =>{
			cy.visit('admin.php?/cp/fields/edit/19')

			cy.get('[data-field-name=col_id_2] .fields-grid-tool-add').first().click()
			cy.get('.fields-grid-setup .fields-grid-item---open .select__button').first().click()
			cy.get('.fields-grid-setup .fields-grid-item---open .select__dropdown-item span:contains("Relationships")').first().click();
			cy.get('.fields-grid-setup .fields-grid-item---open [name*="[col_label]"]').type("Relationship Title");

			cy.get('.fields-grid-setup .fields-grid-item---open #fieldset-relationship_channels .checkbox-label__text div:contains("News")').first().click();
			cy.get('body').type('{ctrl}', {release: false}).type('s')

			cy.visit('admin.php?/cp/fields/groups/edit/1')
			cy.get('.lots-of-checkboxes .checkbox-label__text div:contains("Stupid Grid")').first().click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
		})

		// default, display_entry_id is Off, items haven't been added 
		it('check relationship field in grid', () => {

	      	cy.visit('admin.php?/cp/publish/edit/entry/1')
	      	cy.get('.grid-field a:contains("Add new row")').first().click()
	      	cy.get('.grid-field tr:not(.hidden) button:contains("Relate Entry")').first().click()

			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Welcome to the Example Site!")').first().click({force: true});
			cy.get('.grid-field tr:not(.hidden) a.dropdown__link:contains("Band Title")').first().click({force: true});
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated')
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			//cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
		})

	    //  display_entry_id On, items have been added
	    it('check relationship field with display entry id in grid', () => {
	      cy.visit('admin.php?/cp/fields/edit/19');
	      cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
	      cy.get('[data-toggle-for="relationship_display_entry_id"]').first().click()
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.visit('admin.php?/cp/publish/edit/entry/1')
	      cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').invoke('val').then((val) => { expect(val).to.not.be.equal(" #2 / ") })
	      cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	      cy.get('body').type('{ctrl}', {release: false}).type('s')
	      cy.get('.app-notice---success').contains('Entry Updated');
	      cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
	      cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
	      cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
	      //cy.get('button:contains("Relate Entry")').should('not.be.visible')
	      cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
	      cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	    })

	    //  display_entry_id Off, items have been added
	    it('check relationship field with display entry id in grid', () => {
			cy.visit('admin.php?/cp/fields/edit/19');
			cy.get('.fields-grid-item:last-child .fields-grid-tool-expand').first().click()
			cy.get('[data-toggle-for="relationship_display_entry_id"]').first().click()
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.visit('admin.php?/cp/publish/edit/entry/1')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').parent().find('.list-item__secondary span').should('not.exist')
			cy.get('.grid-field [data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
			cy.get('body').type('{ctrl}', {release: false}).type('s')
			cy.get('.app-notice---success').contains('Entry Updated');
			cy.get('[name=title]').invoke('val').should('eq', "Getting to Know ExpressionEngine")
			cy.get('[name=field_id_1]').invoke('val').should('contain', "Thank you for choosing ExpressionEngine!")
			cy.get('[name=field_id_3]').invoke('val').should('eq', "{filedir_2}ee_banner_120_240.gif");
			//cy.get('button:contains("Relate Entry")').should('not.be.visible')
			cy.get('[data-relationship-react] .list-item__title:contains("Welcome to the Example Site!")').should('exist')
			cy.get('[data-relationship-react] .list-item__title:contains("Band Title")').should('exist')
	    })
	})
})
