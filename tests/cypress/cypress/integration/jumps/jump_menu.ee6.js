/// <reference types="Cypress" />

import JumpMenu from '../../elements/pages/jumps/JumpMenu';
const page = new JumpMenu;

context('Jump Menu', () => {

	function closeJumpMenu() {
		page.get('secondary_input').clear().type('{backspace}')
		page.get('primary_input').focus()
		page.get('primary_input').clear();
		page.get('primary_results').should('be.visible').contains("More than 10 results found, please refine your search")
		//page.get('secondary_results').should('not.be.visible')
		page.get('no_results').should('not.be.visible')

		cy.get("body").type('{esc}');
		page.get('jump_menu').should('not.be.visible')
	}

	before(function() {
        //cy.task('db:seed')
    })

    beforeEach(function() {
		cy.authVisit('admin.php');

		if (Cypress.platform === 'win32') {
			cy.get("body").type('{ctrl}j', {release: false});
		} else {
			cy.get("body").type('{meta}j', {release: false});
		}

        page.get('jump_menu').should('be.visible')
	})

	afterEach(function() {

    })

	describe('entry edit jumps', function() {
		beforeEach(function() {
			page.get('primary_input').type('entr');
			page.get('primary_results').should('be.visible')
			//page.get('secondary_results').should('not.be.visible')
			page.get('no_results').should('not.be.visible')

			page.get('jump_menu').contains("Create Entry")
			page.get('jump_menu').contains("Edit Entry")
			page.get('jump_menu').contains("View Entries")

			cy.server()
		})

		it('create entry', function() {
			cy.route("POST", "**/jumps/**").as("ajax");
			page.get('jump_menu').contains("Create Entry").click()
			cy.wait("@ajax");
			page.get('jump_menu').contains("Information Pages")
			page.get('jump_menu').contains("News")
			closeJumpMenu();
		})

		it('view entries list', function() {
			cy.route("POST", "**/jumps/**").as("ajax");
			page.get('jump_menu').contains("View Entries").click()
			cy.wait("@ajax");
			page.get('jump_menu').contains("Information Pages").click()
			page.get('page_heading').contains("All Information Pages entries")
		})

		it('edit entry', function() {
			cy.route("POST", "**/jumps/**").as("ajax");
			page.get('jump_menu').contains("Edit Entry").click()
			cy.wait("@ajax");
			page.get('jump_menu').contains("Howard")
			page.get('jump_menu').contains("Chloe")
			page.get('secondary_input').type('how');
			cy.wait("@ajax");
			page.get('jump_menu').should("not.contain", "Chloe")
			page.get('jump_menu').contains("Howard")
			page.get('secondary_input').type('{enter}');

			page.get('page_sub_title').contains("Edit Entry")
			page.get('wrap').find('input[name=title]').invoke('val').should("eq", "Howard")
		})

	})


})