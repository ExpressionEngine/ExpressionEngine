import CommunicateSent from '../../elements/pages/utilities/CommunicateSent';
const page = new CommunicateSent
const { _, $ } = Cypress

context('Communicate Sent', () => {

		

		it('shows the sent Emails page (with no results)', () => {
			cy.auth();
			page.load();
			cy.get('.solo').contains('No Sent emails found. Create new Email')
			page.get('pagination').should('not.exist')
   		})

   		it('sorts by subject (asc) and (desc)', () => {
   			cy.auth();
   			page.SubjectSorter()
   		})

   		it('sorts by total sent (asc) and (desc)', () => {
   			//instead of generating new data just use whats already there:
   			page.SentSorter()
   		})

   		it.only('can search by subject', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.subject = "Zeppelins are cool"
			page.runner()
			page.get('phrase_search').type('Zeppelins')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "Zeppelins"')
			page.get('rows').should('have.length',6)
   		})

   		it('can search by message', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.message = "Zeppelins are cool"
			page.runner()
			page.get('phrase_search').type('Zeppelins')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 10 results for "Zeppelins"')
			page.get('rows').should('have.length',11)
   		})

   		it('can search by from name', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.from_name = "Ferdinand von Zeppelin"
			page.runner()
			page.get('phrase_search').type('Ferdinand von Zeppelin')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "Ferdinand von Zeppelin"')
			page.get('rows').should('have.length',6)
   		})

   		it('can search by from email', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.from_email = "ferdinand.von.zeppelin@airships.de"
			page.runner()
			page.get('phrase_search').type('ferdinand.von.zeppelin@airships.de')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "ferdinand.von.zeppelin@airships.de"')
			page.get('rows').should('have.length',6)
   		})

   		it('can search by recipient', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.recipient = "ferdinand.von.zeppelin@airships.de2"
			page.runner()
			page.get('phrase_search').type('ferdinand.von.zeppelin@airships.de2')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "ferdinand.von.zeppelin@airships.de2"')
			page.get('rows').should('have.length',6)
   		})

   		it('can search by cc', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.cc = "ferdinand.von.zeppelin@airships.de3"
			page.runner()
			page.get('phrase_search').type('ferdinand.von.zeppelin@airships.de3')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "ferdinand.von.zeppelin@airships.de3"')
			page.get('rows').should('have.length',6)
   		})


  	    it('can search by bcc', () => {
			cy.auth();
			page.load();

			page.count = 5;
			page.bcc = "ferdinand.von.zeppelin@airships.de4"
			page.runner()
			page.get('phrase_search').type('ferdinand.von.zeppelin@airships.de4')
			page.get('search_submit_button').click()
			page.get('no_results').should('not.exist')
			page.get('heading').contains('we found 5 results for "ferdinand.von.zeppelin@airships.de4"')
			page.get('rows').should('have.length',6)
   		})


   		it('displays "no results" when searching returns nothing', () => {
   			cy.auth();
			page.load();
			page.get('phrase_search').type('ACDC')
			page.get('search_submit_button').click()
			page.get('no_results').should('exist')
   		})


   		it('will paginate at over 26 emails', () => {
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

   		 it('maintains sort while paging', () => {
			cy.auth();
			page.load();
			cy.get(':nth-child(3) > .sort').click({force: true})
			cy.get('a').contains('Next').click()
			cy.get(':nth-child(3) > .sort').should('have.class', 'sort asc')
   		 })

   		  it('maintains sort and search while paging', () => {
			cy.auth();
			page.load();
			page.subject = "Albatross"
			page.count = 40;
			page.runner()
			cy.get(':nth-child(3) > .sort').click({force: true})
			page.get('phrase_search').type('Albatross')
			page.get('search_submit_button').click()

			page.get('no_results').should('not.exist')
			page.get('rows').should('have.length',21) //1 for header
			
			cy.get('a').contains('Next').click()
			cy.get(':nth-child(3) > .sort').should('have.class', 'sort asc')
			page.get('rows').should('have.length',21)
   		 })

   	
   		it('resets the page on a new sort', () => {
			cy.auth();
			page.load();
			page.get('pages').should('have.length',6)

			cy.get('a').contains('Next').click()
			page.get('pages').should('have.length',7)
			cy.get(':nth-child(3) > .sort').click({force: true})
			page.get('pages').should('have.length',6)
   		})


   		it('resets the page on a new search', () => {

   			cy.auth();
			page.load();
			page.get('pages').should('have.length',6)

			cy.get('a').contains('Next').click()
			page.get('pages').should('have.length',7)
			page.get('phrase_search').type('a')
			page.get('search_submit_button').click()
			page.get('pages').should('have.length',6)
			
   		})


   		 it('can view an email', () => {
			cy.auth();
			page.load();
			
			cy.get(':nth-child(1) > :nth-child(4) > .toolbar-wrap > .toolbar > .view > .m-link').click()
			cy.get('.modal-email-1 > .modal').should('exist')
   		 })


   		it('can remove emails in bulk', () => {
			cy.auth();
			page.load();

			cy.get('.check-ctrl > input').click() // select all
			cy.get('select').select('Remove')
			cy.get('.tbl-bulk-act > .btn').click()
			cy.get('.modal-confirm-remove > .modal > .col-group > .col > .form-standard > form > :nth-child(6) > .btn').click()
			cy.get('.app-notice__content > :nth-child(1) > b').contains('Emails removed')
   		})

})