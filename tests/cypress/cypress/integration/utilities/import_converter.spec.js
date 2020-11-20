import 'cypress-file-upload';

import ImportConverter from '../../elements/pages/utilities/Import_file_converter';

const page = new ImportConverter;


context('Import File Converter', () => {

	// it('', () => {

	// })

	// before(function() {
	// 	cy.task('filesystem:create', Cypress.env("TEMP_DIR")+'/about');
	// 	cy.task('filesystem:copy', { from: 'members-comma.txt', to: Cypress.env("TEMP_DIR")+'/about' })
	// 	cy.task('filesystem:copy', { from: 'members-other.txt', to: Cypress.env("TEMP_DIR")+'/about' })
	// 	cy.task('filesystem:copy', { from: 'members-pipe.txt', to: Cypress.env("TEMP_DIR")+'/about' })
	// 	cy.task('filesystem:copy', { from: 'members-tab.txt', to: Cypress.env("TEMP_DIR")+'/about' })

	// })

	// after(function() {
	// 		cy.task('filesystem:delete', Cypress.env("TEMP_DIR")+'/about')

	// })

	beforeEach(function() {
		cy.auth();
	    page.load()
	    cy.hasNoErrors()

	})

	const comma = 'members-comma.txt';
	const other = 'members-other.txt';
	const pipe = 'members-pipe.txt';
	const tab = 'members-tab.txt';

	it('shows the Import File Converter page', () => {
		page.get('wrap').contains('Import File Converter')
		page.get('wrap').contains('Member file')
		page.get('delimiter')
		page.get('enclosing_char')
	})


	it('should validate the form: No File Input produces ERROR (Should validate assigned fields)', () => {
		//No file checking
		page.get('send_it').first().click()
		cy.get('body').contains('Attention: File not converted')
		cy.get('body').contains('This field is required')
		
	})

	it('should validate the way files are delimited Tab', () => {
		page.submit(tab, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

	})

	it('should validate the way files are delimited Comma', () => {
		page.submit(comma, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Pipe').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

	})

	it('should validate the way files are delimited Pipe', () => {
		page.submit(pipe, 'text/plain', 'input[name="member_file"]')
    	page.get('delimiter').contains('Comma').first().click()
    	cy.wait(400)
    	page.get('send_it').first().click()
    	cy.get('body').contains('You must have at least 3 fields')

	})

		

})


