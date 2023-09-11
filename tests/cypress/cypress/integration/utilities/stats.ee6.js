import Stats from '../../elements/pages/utilities/Stats';

const page = new Stats;


context('Statistics', () => {

    before(function(){
        cy.task('db:seed')
    })
    
	  beforeEach(function() {
	    cy.authVisit(page.url);
	    page.get('heading').contains("Manage Statistics")

	  })

	  //WORKS!
	  it('shows the Manage Statistics page', () => {
	  	 //let menu = ['Channel Entries', 'Members', 'Sites']
	   	 cy.get('table').find('thead').should('have.length', 1)
	     cy.get('table').find('tbody').find('tr').should('have.length', 3)

	     cy.get('table').find('tbody').find('tr').eq(0).contains("Channel Entries")
	     cy.get('table').find('tbody').find('tr').eq(1).contains("Members")
	     cy.get('table').find('tbody').find('tr').eq(2).contains("Sites")

	     cy.get('table').find('tbody').find('tr').eq(0).contains("10")
	     cy.get('table').find('tbody').find('tr').eq(1).contains("7")
	     cy.get('table').find('tbody').find('tr').eq(2).contains("1")
	  })


	  //Works
	  it('Sort stats by source', () =>{
	  	//cy.get('all').find('a.column-sort').eq(0).click()
	  	cy.get('table').find('thead').find('tr').find('th').eq(0).find('a').click()

	  	page.get('content_table').find('th.column-sort-header--active').contains('Source')

	  	cy.get('table').find('tbody').find('tr').eq(2).contains("Channel Entries")
	    cy.get('table').find('tbody').find('tr').eq(1).contains("Members")
	    cy.get('table').find('tbody').find('tr').eq(0).contains("Sites")

	    cy.get('table').find('thead').find('tr').find('th').eq(0).find('a').click()

	    cy.get('table').find('tbody').find('tr').eq(0).contains("Channel Entries")
	    cy.get('table').find('tbody').find('tr').eq(1).contains("Members")
	    cy.get('table').find('tbody').find('tr').eq(2).contains("Sites")
	     page.get('content_table').find('th.column-sort-header--active').contains('Source')
	  })

	  it('Sort stats by count', () =>{
	  	//cy.get('all').find('a.column-sort').eq(0).click()
	  	cy.get('table').find('thead').find('tr').find('th').eq(1).find('a').click()

	  	page.get('content_table').find('th.column-sort-header--active').contains('Record Count')
	  	cy.get('table').find('tbody').find('tr').eq(0).contains("1")
	    cy.get('table').find('tbody').find('tr').eq(1).contains("7")
	    cy.get('table').find('tbody').find('tr').eq(2).contains("10")

	    cy.get('table').find('thead').find('tr').find('th').eq(1).find('a').click()

	     cy.get('table').find('tbody').find('tr').eq(0).contains("10")
	     cy.get('table').find('tbody').find('tr').eq(1).contains("7")
	     cy.get('table').find('tbody').find('tr').eq(2).contains("1")
	     page.get('content_table').find('th.column-sort-header--active').contains('Record Count')
	  })


	  it.skip('reports accurate record count after adding a member', () =>{
	  	page.add_member(username, 'johndoe') //TODO Block, ask Bryan what is add_member?

	  	cy.get('table').find('thead').should('have.length', 1)
	    cy.get('table').find('tbody').find('tr').should('have.length', 3)
	    cy.get('table').find('tbody').find('tr').eq(0).contains("Channel Entries")
	    cy.get('table').find('tbody').find('tr').eq(1).contains("Members")
	    cy.get('table').find('tbody').find('tr').eq(2).contains("Sites")

	    cy.get('table').find('tbody').find('tr').eq(0).contains("10")
	    cy.get('table').find('tbody').find('tr').eq(1).contains("8")
	    cy.get('table').find('tbody').find('tr').eq(2).contains("1")
	  })



	  it('Sync one source', () =>{
	  	page.get('content_table').find('tr').eq(2).find('a').click()
	  	page.hasAlert('success')
	  })


	  it('Sync multiple sources', () =>{
	  	page.get('rows').find('input[type="checkbox"]').eq(0).check()
	  	page.get('bulk_action').select("Sync")
	  	page.get('action_submit_button').click()
	  	page.hasAlert('success')

	  })



})