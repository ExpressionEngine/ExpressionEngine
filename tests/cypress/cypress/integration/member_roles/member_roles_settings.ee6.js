import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Settings ', () => {

	it('Creates Settings Manager Role', () => {
		cy.visit('admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('admin.php?/cp/members/roles')
		cy.get('a').contains('New Role').click()
		cy.get('input[name="name"]').clear().type('SettingManager')
		cy.get('button').contains('Save & Close').eq(0).click()

	})

	it('adds a Settings Manager member', () => {
		cy.visit('admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();
		add_members('SettingManager',1)
	})

	it('Setting Manager can not login because cp access has not been given yet',() => {
	   cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('SettingManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Addon Role access Settings and CP', () => {
	   cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


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
		cy.visit('admin.php?/cp/login');
	  	cy.get('#username').type('SettingManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

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
		cy.visit('admin.php?/cp/login');
	  	cy.get('#username').type('SettingManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('SettingManager1')


	   cy.get('.ee-sidebar').contains('Settings').click()



	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('HTML Buttons')
	   cy.get('.box').contains('Hit Tracking')
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
	   cy.get('.box').contains('Hit Tracking')
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

	   cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('SettingManager').click()
	   cy.get('button').contains('CP Access').click()
		cy.get('#fieldset-can_manage_consents .toggle-btn').click();//turn off  access
		cy.get('button').contains('Save').eq(0).click()

		logout()

		cy.visit('admin.php?/cp/login');
	  	cy.get('#username').type('SettingManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('SettingManager1')


	   cy.get('.ee-sidebar').contains('Settings').click()

	   cy.get('.box').contains('General Settings')
	   cy.get('.box').contains('URL and Path Settings')
	   cy.get('.box').contains('Debugging & Output')


	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('HTML Buttons')
	   cy.get('.box').contains('Hit Tracking')
	   cy.get('.box').contains('Word Censoring')
	   cy.get('.box').contains('Menu Manager')

	   cy.get('.box').contains('Settings')
	   cy.get('.box').contains('Access Throttling')
	   cy.get('.box').contains('CAPTCHA')
	   cy.get('.box').should('not.contain','Consent Requests')


	})

	it.skip('Cleans for reruns', () =>{
		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(5) input').click();


	   cy.get('select').select('Delete')
    	cy.get('.bulk-action-bar > .button').click()
    	cy.get('.modal-confirm-delete > .modal > form > .dialog__actions > .dialog__buttons > .button-group > .btn').click()
    	cy.visit('admin.php?/cp/members')


	    cy.get('tr:nth-child(1) > td > input').click();

	    cy.get('select').select('Delete');

	    cy.get('.button--primary').click();

	    cy.get("body").then($body => {
	          if ($body.find("#fieldset-verify_password > .field-control > input").length > 0) {   //evaluates as true if verify is needed
	              cy.get("#fieldset-verify_password > .field-control > input").type('password');
	          }
	    });
	    //Sometimes it asks for password to delete users and sometimes it does not.

	    cy.get('.button--danger').click();
	    cy.get('.modal-confirm-delete form').submit();
	})


})


function add_members(group, count){
  let i = 1;
  for(i ; i <= count; i++){
    member.load() //goes to member creation url

    let email = group;
    email += i.toString();
    email += "@test.com";
    let username = group + i.toString();
    member.get('username').clear().type(username)
      member.get('email').clear().type(email)
      member.get('password').clear().type('password')
      member.get('confirm_password').clear().type('password')

    cy.get("body").then($body => {
          if ($body.find("input[name=verify_password]").length > 0) {   //evaluates as true if verify is needed
              cy.get("input[name=verify_password]").type('password');
          }
        });
      cy.get('button').contains('Roles').click()
	cy.get('label').contains(group).click()
	cy.get('.form-btns-top .saving-options').click()
    member.get('save_and_new_button').click()
  }
}

function logout(){
  cy.visit('admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}