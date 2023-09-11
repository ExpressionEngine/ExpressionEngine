import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Settings Permissions', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('SettingManager')
		cy.addMembers('SettingManager', 1)
		cy.logout();
	})

	it('Setting Manager can not login because cp access has not been given yet',() => {
		cy.auth({
			email: 'SettingManager1',
			password: 'password'
		})

	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Add-on Role access Settings and CP', () => {
	   cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('SettingManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_sys_prefs .toggle-btn').click();
		cy.get('#fieldset-can_access_security_settings .toggle-btn').click();
		cy.get('#fieldset-can_manage_consents .toggle-btn').click();//turn everything on to start with

		cy.get('button').contains('Save').eq(0).click()
	})

	it('Can see the Settings now but nothing else',() => {
		cy.auth({
			email: 'SettingManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('SettingManager1')

		cy.get('.ee-sidebar').contains('Settings').should('exist')


	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	})

	it('Can Access all Settings and no errors when accessed',() => {
		cy.auth({
			email: 'SettingManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('SettingManager1')


	   cy.get('.ee-sidebar').contains('Settings').click()



	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('HTML Buttons')
	   cy.get('.box').contains('Tracking')
	   cy.get('.box').contains('Word Censoring')
	   cy.get('.box').contains('Menu Manager')

	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('Access Throttling')
	   cy.get('.box').contains('CAPTCHA')
	   cy.get('.box').contains('Consent Requests')







	   cy.get('.box').contains('URL and Path Settings')
	   cy.hasNoErrors()

	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Debugging & Output')
	   cy.hasNoErrors()


	   //Content
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Settings')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('HTML Buttons')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Tracking')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Word Censoring')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Menu Manager')
	   cy.hasNoErrors()

	   //Security and Privacy
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Settings')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Access Throttling')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('CAPTCHA')
	   cy.hasNoErrors()
	   cy.get('.ee-sidebar').contains('Settings').click()
	   cy.get('.box').contains('Consent Requests')
	   cy.hasNoErrors()


	})

	it('Loses Acccess to Content',() => {

	   cy.auth();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('SettingManager').click()
	   cy.get('button').contains('CP Access').click()
		cy.get('#fieldset-can_manage_consents .toggle-btn').click();//turn off  access
		cy.get('button').contains('Save').eq(0).click()

		cy.logout()

		cy.auth({
			email: 'SettingManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('SettingManager1')


	   cy.get('.ee-sidebar').contains('Settings').click()

	   cy.get('.box').contains('General Settings')
	   cy.get('.box').contains('URL and Path Settings')
	   cy.get('.box').contains('Debugging & Output')


	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('HTML Buttons')
	   cy.get('.box').contains('Tracking')
	   cy.get('.box').contains('Word Censoring')
	   cy.get('.box').contains('Menu Manager')

	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('Access Throttling')
	   cy.get('.box').contains('CAPTCHA')
	   cy.get('.box').should('not.contain','Consent Requests')


	})

})
