import MemberImport from '../../elements/pages/utilities/Member_import';

const page = new MemberImport;


context('Member Import', () => {

    before(function(){
        cy.task('db:seed')
    })

	beforeEach(function() {
		let field_required = 'This field is required.'
		cy.auth();
	    page.load()
	    cy.hasNoErrors()
	})


	it('shows the Member Import page', () => {
		page.get('general_radio').contains('Banned').should('exist')
		page.get('general_radio').contains('Guests').should('exist')
		page.get('general_radio').contains('Members').should('exist')
		page.get('general_radio').contains('Super Admin').should('exist')
		page.get('general_radio').contains('Pending').should('exist')
		page.get('general_radio').contains('mm/dd/yyyy').should('exist')
		page.get('general_radio').contains('dd/mm/yyyy').should('exist')
		page.get('general_radio').contains('dd-mm-yyyy').should('exist')
		page.get('general_radio').contains('yyyy-mm-dd').should('exist')
	})


	it('can import basic xml', () =>{
		const fileName = 'members.xml'
    	page.submit(fileName, 'application/xml', 'input[name="member_xml_file"]') 
    	page.get('general_radio').contains('Banned').click()
    	page.get('send_it').first().click()
    	cy.get('button').contains('Confirm').first().click()
    	cy.get('body').contains('Total of 4 members imported.')
    	cy.hasNoErrors()
	})

	it('dont allow duplicate data', () =>{
		const fileName = 'members.xml'
    	page.submit(fileName, 'application/xml', 'input[name="member_xml_file"]') 
    	page.get('general_radio').contains('Banned').click()
    	page.get('send_it').first().click()
    	cy.get('button').contains('Confirm').first().click()
    	cy.get('body').contains("The username you chose is not available (Username: 'Import1' - within user")
	})

	it('does not import invalid XML data', () => {
		const fileName = 'invalid.xml'
    	page.submit(fileName, 'application/xml', 'input[name="member_xml_file"]') 
    	page.get('general_radio').contains('Banned').click()
    	page.get('send_it').first().click()
    	cy.get('button').contains('Confirm').first().click()
    	cy.get('body').contains('Check the XML file for any incorrect syntax.')
    	cy.get('body').contains('Unable to parse XML')
	})
})
