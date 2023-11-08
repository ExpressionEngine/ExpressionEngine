import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Template Permissions', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('TempManager')
		cy.addMembers('TempManager', 1)

		cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('TempManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_design .toggle-btn').click();
		cy.get('#fieldset-can_admin_design .toggle-btn').click();

		cy.get('button').contains('Save').eq(0).click()

		cy.logout()
	})


	it('Can see CP and Temp', () => {
		cy.auth({
			email: 'TempManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')



		cy.get('.ee-sidebar').contains('Developer').should('exist')


	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

		cy.get('.ee-sidebar__item[title="Templates"]').click()

	   cy.get('.box').should('not.contain', 'Template Groups')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')

	    cy.get("a").contains('New').then($button => {
	  if ($button.is(':visible')){
	    	assert.equal(3, 5, 'The new a link should not exist')
		  }
		})

	})

	it('Can turn on groups', () => {
	   cy.auth();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()


	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(1) > input').click();
	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(2) > input').click();
	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(3) > input').click();
 //turn off security & privacy
		cy.get('button').contains('Save').eq(0).click()

		cy.logout()

		cy.auth({
			email: 'TempManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')



		cy.get('.ee-sidebar').contains('Developer').should('exist')


	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar__item[title="Templates"]').click()

	   cy.get('.box').contains('No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')
	   cy.get('.box').should('not.contain','Template Partials')
	   cy.get('a').contains('New').should('exist')

	   cy.get("a").contains('New').then($button => {
	  if ($button.is(':visible')){
	    	assert.equal(3, 3, 'pass test')
		  }
		})
	})

	it('can turn on partials', () =>{
		cy.auth();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()



		cy.get('#fieldset-template_partials .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-template_partials .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-template_partials .checkbox-label:nth-child(3) > input').click();

		cy.get('button').contains('Save').eq(0).click()

		cy.logout()

		cy.auth({
			email: 'TempManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')



		cy.get('.ee-sidebar').contains('Developer').should('exist')


	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar__item[title="Templates"]').click()

	   cy.get('.box').contains('No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')
	   cy.get('.box').contains('Template Partials')
	})


	it('can turn on variables', () =>{
		cy.auth();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()



		cy.get('#fieldset-template_variables .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-template_variables .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-template_variables .checkbox-label:nth-child(3) > input').click();


		cy.get('button').contains('Save').eq(0).click()

		cy.logout()

		cy.auth({
			email: 'TempManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')


		cy.get('.ee-sidebar').contains('Developer').should('exist')
	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar__item[title="Templates"]').click()

	   cy.get('.box').contains('No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')
	   cy.get('.box').contains('Template Variables')
	})

})
