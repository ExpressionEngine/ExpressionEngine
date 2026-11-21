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
        cy.task('db:seed')
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

	it('keeps the existing Consents shortcut as the first match', function() {
		page.get('primary_input').type('con');
		page.get('primary_results').find('.jump-menu__link--active').should('contain.text', 'Consents');
		page.get('primary_input').type('{enter}');
		cy.url().should('include', '/members/profile/consent');
	});

	it('switches to Contrast from the theme picker and remembers it', function() {
		page.get('primary_input').type('switch theme{enter}');
		page.get('secondary_results').contains('.jump-menu__link', /^\s*contrast\s*$/i).click();
		cy.get('body').should('have.attr', 'data-theme', 'contrast');
		cy.reload();
		cy.get('body').should('have.attr', 'data-theme', 'contrast');
	});

	describe('entry edit jumps', function() {
		beforeEach(function() {
			page.get('primary_input').type('entr');
			page.get('primary_results').should('be.visible')
			//page.get('secondary_results').should('not.be.visible')
			page.get('no_results').should('not.be.visible')

			page.get('jump_menu').contains("Create Entry")
			page.get('jump_menu').contains("Edit Entry")
			page.get('jump_menu').contains("View Entries")
		})

		it('create entry', function() {
			cy.intercept("POST", "**/jumps/**").as("ajax");
			page.get('jump_menu').contains("Create Entry").click()
			cy.wait("@ajax");
			page.get('jump_menu').contains("Information Pages")
			page.get('jump_menu').contains("News")
			closeJumpMenu();
		})

		it('edit entry', function() {
			cy.intercept("POST", "**/jumps/**").as("ajax");
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
