Cypress.config().baseUrl = 'localhost';

import Checkbox from '../../elements/pages/specificFields/Checkboxes';
import CreateField from '../../elements/pages/field/CreateField';

const check = new Checkbox;
const page = new CreateField;

context('A way to create Checkbox fields', () => {

	beforeEach(function() {
      cy.visit('http://localhost/admin.php')
      cy.get('#username').type('admin')
      cy.get('#password').type('password')
      cy.get('.button').click()
  	})

  	it('verifies fields page exists', () => {
	  	cy.visit('http://localhost/admin.php?/cp/fields')
	  	cy.get('.main-nav__title > h1').contains('Field')
	  	cy.get('.main-nav__toolbar > .button').contains('New Field')
	  	cy.get('.filter-bar').should('exist')
	  	cy.get('.filter-bar').should('exist')
	})

	it.only('Tests',() => {
		cy.visit('http://localhost/admin.php?/cp/fields')
	  	cy.get('.main-nav__toolbar > .button').contains('New Field').click()
	  	page.get('Type').click()
	  	page.get('Type_Options').contains('Radio Buttons').click() 

	})
})