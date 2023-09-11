import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
import AddonManager from '../../elements/pages/addons/AddonManager';
const addonManager = new AddonManager;
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Add-ons Permissions', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('AddonManager')
		cy.addMembers('AddonManager', 1)
		cy.logout();
	})

	it('Add-on Manager can not login because cp access has not been given yet',() => {
		cy.auth({
			email: 'AddonManager1',
			password: 'password'
		})
		cy.hasNoErrors()
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Add-on Role access Add-ons and CP', () => {
		cy.auth()

		cy.hasNoErrors()


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('AddonManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP

		cy.get('#fieldset-can_access_addons .toggle-btn').click();
		cy.get('#fieldset-can_admin_addons .toggle-btn').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(4) > input').click();

		cy.get('button').contains('save').eq(0).click()
	})

	it('Can see the Addons now but nothing else',() => {
		cy.auth({
			email: 'AddonManager1',
			password: 'password'
		})

		cy.hasNoErrors()

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.dismissLicenseAlert()
		cy.get('h1').contains('AddonManager1')
		cy.get('.main-nav__account-icon > img').click()
		//


		cy.get('.ee-sidebar').contains('Add-Ons').should('exist')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')

	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	})

	it('Can Access all Addons and has the option to Uninstall them',() => {
		cy.auth({
			email: 'AddonManager1',
			password: 'password'
		})

		cy.hasNoErrors()


		cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('AddonManager1')
	   cy.dismissLicenseAlert()
	   cy.get('.main-nav__account-icon > img').click()
	   //

	   cy.get('.ee-sidebar').contains('Add-Ons').click()

	   cy.contains('Email')
	   cy.contains('Rich Text Editor')
	   cy.contains('Statistics')

	   addonManager.get('addons').eq(1).then((addon_card) => {
		const addon_name = addon_card.find('.add-on-card__title').contents().filter(function(){ return this.nodeType == 3; }).text().trim();
		cy.log(addon_name);
		let btn = addon_card.find('.js-dropdown-toggle')
		cy.get(btn).should('exist')
		cy.get(btn).trigger('click')
		cy.get(btn).next('.dropdown').find('a:contains("Uninstall")').click()

		addonManager.get('modal_submit_button').contains('Confirm, and Uninstall').click() // Submits a form
		cy.hasNoErrors()
		
		// The filter should not change
		page.hasAlert()

		page.get('alert').contains("Add-Ons Uninstalled")
		page.get('alert').contains(addon_name);

	})

	})

}) //End Context
