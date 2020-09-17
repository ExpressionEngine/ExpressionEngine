import DebugExtensions from '../../elements/pages/utilities/DebugExtensions';

const page = new DebugExtensions;


context('Debug Extensions', () => {

	 beforeEach(function() {
	    cy.authVisit(page.url);
	    page.get('heading').contains("Manage Add-on Extensions")
	 })

	 	//Works!
	  it('shows the Manage Add-on Extensions page', () => {
	    page.get('addon_name_header').should('have.class', 'highlight')
	    cy.get('table').find('thead').should('have.length', 1)
	    cy.get('table').find('tbody').should('have.length', 1)
	  })

	  //Works!
	  it('can disable and enable an extension', () => {
 	  	
	 	  	page.get('statuses').find('span').contains("Enabled")
	 	  	page.get('checkbox_header').find('input[type="checkbox"]').check()
	 	  	page.get('bulk_action').select("Disable")
	 	  	page.get('action_submit_button').click()
	 	  	//cy.hasNoErrors()

	 	  	page.get('statuses').find('span').contains("Disabled")
	 	  	page.get('checkbox_header').find('input[type="checkbox"]').check()
	 	  	page.get('bulk_action').select("Enable")
	 	  	 page.get('action_submit_button').click()
	 	  	// cy.hasNoErrors()

	 	  	 page.get('statuses').find('span').contains("Enabled")

 	  })

	  //Works!
	  it('can navigate to a manual page', () =>{

	  		cy.get('ul.toolbar li.manual a').click

	  })

})