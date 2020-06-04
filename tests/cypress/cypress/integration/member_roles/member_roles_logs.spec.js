import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Test Member roles Utilities ', () => {

	it('Creates Log Manager Role', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
		cy.get('a').contains('New Role').click()
		cy.get('input[name="name"]').clear().type('LogManager')
		cy.get('button').contains('Save & Close').eq(0).click()

	})

	it('adds a Log  Manager member', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();
		add_members('LogManager',1)
	})

	it('Log Manager can not login because cp access has not been given yet',() => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('LogManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Addon Role access Utils and CP', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('LogManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_logs .toggle-btn').click(); //access slog

		cy.get('button').contains('Save').eq(0).click()
	})

	it('Can get to Logs now', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	    cy.get('#username').type('LogManager1');
	    cy.get('#password').type('password');
	    cy.get('.button').click();

	    cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('LogManager1')
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Logs').click()

	  cy.get('.box').contains('Control Panel')
	  cy.get('.box').contains('Throttling')
	  cy.get('.box').contains('Email')
	  cy.get('.box').contains('Search')

	})

	it('Loses access', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('LogManager').click()

	   cy.get('button').contains('CP Access').click()
	   


		cy.get('#fieldset-can_access_logs .toggle-btn').click(); //turn off slog

		cy.get('button').contains('Save').eq(0).click()

		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	    cy.get('#username').type('LogManager1');
	    cy.get('#password').type('password');
	    cy.get('.button').click();

	    cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('LogManager1')
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
	   cy.get('.ee-sidebar').should('not.contain','Developer').click()
	})


	it('cleans for reruns', () =>{
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(3) input').click();
	

	   cy.get('select').select('Delete')

    	cy.get('.bulk-action-bar > .button').click()
    	cy.get('.modal-confirm-delete > .modal > form > .dialog__actions > .dialog__buttons > .button-group > .btn').click()
    	cy.visit('http://localhost:8888/admin.php?/cp/members')


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





})//Contex






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
      member.get('save_and_new_button').click()
  }
}

function logout(){
  cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}