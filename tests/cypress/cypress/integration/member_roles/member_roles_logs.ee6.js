import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Member Roles / Logs Permissions ', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('LogManager')
		cy.addMembers('LogManager', 1)
		cy.logout()
	})


	it('Log Manager can not login because cp access has not been given yet',() => {
		cy.auth({
			email: 'LogManager1',
			password: 'password'
		})

	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Add-on Role access Utils and CP', () => {
	   cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('LogManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_logs .toggle-btn').click(); //access slog

		cy.get('button').contains('Save').eq(0).click()
	})

	it('Can get to Logs now', () => {
		cy.auth({
			email: 'LogManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('LogManager1')

	   page.open_dev_menu()
    	cy.contains('Logs').click()

	  cy.get('.box').contains('Control Panel')
	  cy.get('.box').contains('Throttling')
	  cy.get('.box').contains('Email')
	  cy.get('.box').contains('Search')

	})

	it('Loses access', () => {
		cy.auth()


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('LogManager').click()

	   cy.get('button').contains('CP Access').click()



		cy.get('#fieldset-can_access_logs .toggle-btn').click(); //turn off slog

		cy.get('button').contains('Save').eq(0).click()

		cy.logout()

		cy.auth({
			email: 'LogManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('LogManager1')
	   cy.get('.ee-sidebar').should('not.contain','Developer').click()
	})

})//Contex