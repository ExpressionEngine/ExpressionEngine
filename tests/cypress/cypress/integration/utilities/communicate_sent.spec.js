import CommunicateSent from '../../elements/pages/utilities/CommunicateSent';
const page = new CommunicateSent
const { _, $ } = Cypress

context.skip('Communicate Sent', () => {

		

		it('shows the sent Emails page (with no results)', () => {
			cy.auth();
			page.load();
			page.get('no_results').should('exist')
			cy.get('.solo').contains('No Sent emails found. Create new Email')
			page.get('pagination').should('not.exist')
   		})

   	
		let Subject = "Led Zeppelins"
		let Body = "Are Cool"
		let Group = "Super Admin"

   		it('can search by subject', () => {
			cy.auth();
			page.load();
			cy.get('a').contains('Create new').first().click()
			page.send(Subject, Body, Group)

			
			page.get('phrase_search').type('Led Zeppelins')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 1')
			page.get('rows').should('have.length',1)
   		})

   		it('can search by message', () => {
			cy.auth();
			page.load();

			page.get('phrase_search').type('Are Cool')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 1')
			
   		})

   		

   		it('can search by from email', () => {
			cy.auth();
			page.load();

			page.get('phrase_search').type('test@test.com')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 1')
			page.get('rows').should('have.length',1)
   		})

   		let cc = "test@testcc.com"
   		let bcc = "test@testbcc.com"

   		it('can search by cc', () => {
			cy.auth();
			page.load();
			cy.get('a').contains('Create new').first().click()
			page.send_detail(Subject,Body,Group,cc,bcc)
			page.get('phrase_search').type('test@testcc.com')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 1 results for')
			page.get('rows').should('have.length',1)
   		})


  	    it('can search by bcc', () => {
			cy.auth();
			page.load();

			
			page.get('phrase_search').type('test@testbcc.com')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 1 results for')
			page.get('rows').should('have.length',1)
   		})


   		it('displays "no results" when searching returns nothing', () => {
   			cy.auth();
			page.load();
			page.get('phrase_search').type('ACDC')
			page.get('search_submit_button').click()
			page.get('no_results').should('exist')
   		})


   		it('will paginate at over 26 emails', () => {
   			let a = 0;
   			cy.get('a').contains('Create new').first().click()
    		for(a;a<28;a++){
    			page.send(Subject,Body,Group)
    		}
			cy.auth();
			page.load();
			page.get('pagination').should('exist')
   		})


   		it('will show the Prev button when on page 2', () => {
			cy.auth();
			page.load();
			cy.get('a').contains('Next').click()
			cy.get('a').contains('Previous').should('exist')
   		})


   		it('will not show Next on the last page', () => {
			cy.auth();
			page.load();
			cy.get('a').contains('Last').click()
			cy.get('a').contains('Previous').should('exist')
			cy.get('a').contains('Next').should('not.exist')
   		})

})
