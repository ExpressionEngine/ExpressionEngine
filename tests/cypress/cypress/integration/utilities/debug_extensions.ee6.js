import DebugExtensions from '../../elements/pages/utilities/DebugExtensions';
import AddonManager from '../../elements/pages/addons/AddonManager';

const page = new DebugExtensions;
const addons = new AddonManager;


context('Debug Extensions', () => {

	before(function(){
		cy.task('db:seed');

		cy.authVisit(addons.url);
		addons.get('uninstalled_addons').find('.add-on-card__title').contains('Spam').parents('.add-on-card').find('.add-on-card__button a').first().click()
	})

	 beforeEach(function() {
	    cy.authVisit(page.url);
	    page.get('heading').contains("Manage Add-on Extensions")
	 })

	 	//Works!
	  it('shows the Manage Add-on Extensions page', () => {
	    page.get('addon_name_header').should('have.class', 'column-sort-header--active')
	    cy.get('table').find('thead').should('have.length', 1)
	    cy.get('table').find('tbody').should('have.length', 1)
	  })

	  //Works!
	  it('can disable and enable an extension', () => {

	 	  	page.get('statuses').contains("enabled")
	 	  	page.get('checkbox_header').find('input[type="checkbox"]').check()
	 	  	page.get('bulk_action').select("Disable")
	 	  	page.get('action_submit_button').click()
	 	  	//cy.hasNoErrors()

	 	  	page.get('statuses').contains("disabled")
	 	  	page.get('checkbox_header').find('input[type="checkbox"]').check()
	 	  	page.get('bulk_action').select("Enable")
	 	  	 page.get('action_submit_button').click()
	 	  	// cy.hasNoErrors()

	 	  	 page.get('statuses').contains("enabled")

 	  })


	  /*it('can navigate to a manual page', () =>{

	  		//cy.get('ul.toolbar li.manual a').click AJ
	  		cy.get('a[title="Manual"]').click()
	  		cy.hasNoErrors()

	  })*/

})