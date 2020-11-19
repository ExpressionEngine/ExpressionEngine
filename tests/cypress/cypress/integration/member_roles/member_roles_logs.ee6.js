import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Test Member roles Utilities ', () => {

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

	it('Let Addon Role access Utils and CP', () => {
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


	it.skip('cleans for reruns', () =>{
		cy.auth({
			email: 'LogManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(3) input').click();


	   cy.get('select').select('Delete')

    	cy.get('.bulk-action-bar > .button').click()
    	cy.get('.modal-confirm-delete > .modal > form > .dialog__actions > .dialog__buttons > .button-group > .button').click()
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
	cy.get('.form-btns-top .saving-options').click()
    member.get('save_and_new_button').click()
  }
}

function logout(){
  cy.visit('admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}