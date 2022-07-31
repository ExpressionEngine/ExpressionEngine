import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Utilities ', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('UtilManager')
		cy.addMembers('UtilManager', 1)

		cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('UtilManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP



		cy.get('#fieldset-can_access_utilities .toggle-btn').click()

		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(2) input').click();
		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(3) input').click();
		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(4) input').click();
		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(5) input').click();

		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(1) > .checkbox-label > input').last().click();
		cy.get('button').contains('Save').eq(0).click()

		cy.logout()
	})

	it('Can get to Utils now', () => {
		cy.auth({
			email: 'UtilManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('UtilManager1')
	   //
	  //
	  page.open_dev_menu()
	  cy.contains('Utilities').click()

	   cy.get('.box').contains('Send Email')
	   cy.get('.box').contains('Sent')

	   cy.get('.box').contains('CP Translations')
	   cy.get('.box').contains('PHP Info')

	   cy.get('.box').contains('File Converter')
	   cy.get('.box').contains('Member Import')

	   cy.get('.box').contains('Back Up Database')
	   cy.get('.box').contains('SQL Manager')
	   cy.get('.box').contains('Query Form')

	   cy.get('.box').contains('Cache Manager')
	   cy.get('.box').contains('Search Reindex')
	   cy.get('.box').contains('Statistics')
	   cy.get('.box').contains('Search and Replace')

	})

	it('Loses Communication', () => {
		cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('UtilManager').click()

	   cy.get('button').contains('CP Access').click()


		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(1) > .checkbox-label > input').last().click(); //turn off access to communicate
		cy.logout()

		cy.auth({
			email: 'UtilManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('UtilManager1')
	   //
	  //
	  page.open_dev_menu()
	  cy.contains('Utilities').click()

	   cy.get('.box').contains('CP Translations')
	   cy.get('.box').contains('PHP Info')

	   cy.get('.box').contains('File Converter')
	   cy.get('.box').contains('Member Import')

	   cy.get('.box').contains('Back Up Database')
	   cy.get('.box').contains('SQL Manager')
	   cy.get('.box').contains('Query Form')

	   cy.get('.box').contains('Cache Manager')
	   cy.get('.box').contains('Search Reindex')
	   cy.get('.box').contains('Statistics')
	   cy.get('.box').contains('Search and Replace')

	   cy.get('.box').should('not.contain','Send Email')
	   cy.get('.box').should('not.contain','Sent')
	})

	it('Loses Translations',() =>{

		cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('UtilManager').click()

	   cy.get('button').contains('CP Access').click()



		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(2) input').last().click(); //turn off access to Translations
		cy.logout()

		cy.auth({
			email: 'UtilManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('UtilManager1')

	   page.open_dev_menu()
		cy.contains('Utilities').click()


	   cy.get('.box').contains('PHP Info')

	   cy.get('.box').contains('File Converter')
	   cy.get('.box').contains('Member Import')

	   cy.get('.box').contains('Back Up Database')
	   cy.get('.box').contains('SQL Manager')
	   cy.get('.box').contains('Query Form')

	   cy.get('.box').contains('Cache Manager')
	   cy.get('.box').contains('Search Reindex')
	   cy.get('.box').contains('Statistics')
	   cy.get('.box').contains('Search and Replace')

	   cy.get('.box').should('not.contain','Send Email')
	   cy.get('.box').should('not.contain','Sent')
	   cy.get('.box').should('not.contain','CP Translations')

	})

	it('loses Import',() => {
		cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('UtilManager').click()

	   cy.get('button').contains('CP Access').click()


		cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(3) input').click();
 				//turn off access to Imports
		cy.logout()

		cy.auth({
			email: 'UtilManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('UtilManager1')
	   //
	  //
	  page.open_dev_menu()
	  cy.contains('Utilities').click()


	   cy.get('.box').contains('PHP Info')



	   cy.get('.box').contains('Back Up Database')
	   cy.get('.box').contains('SQL Manager')
	   cy.get('.box').contains('Query Form')

	   cy.get('.box').contains('Cache Manager')
	   cy.get('.box').contains('Search Reindex')
	   cy.get('.box').contains('Statistics')
	   cy.get('.box').contains('Search and Replace')

	   cy.get('.box').should('not.contain','Send Email')
	   cy.get('.box').should('not.contain','Sent')
	   cy.get('.box').should('not.contain','CP Translations')
	   cy.get('.box').should('not.contain','File Converter')
	   cy.get('.box').should('not.contain','Member Import')


	})

	it('loses SQL Manager',() => {

		cy.auth();


	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('UtilManager').click()

	   cy.get('button').contains('CP Access').click()



			cy.get('.field-inputs:nth-child(1) > .nestable-item:nth-child(4) input').click();

 				//turn off access to SQL
		cy.logout()

		cy.auth({
			email: 'UtilManager1',
			password: 'password'
		})

	    cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('UtilManager1')
	   //
	  //
	  page.open_dev_menu()
	  cy.contains('Utilities').click()


	   cy.get('.box').contains('PHP Info')



	   cy.get('.box').contains('Cache Manager')
	   cy.get('.box').contains('Search Reindex')
	   cy.get('.box').contains('Statistics')
	   cy.get('.box').contains('Search and Replace')

	   cy.get('.box').should('not.contain','Send Email')
	   cy.get('.box').should('not.contain','Sent')
	   cy.get('.box').should('not.contain','CP Translations')
	   cy.get('.box').should('not.contain','File Converter')
	   cy.get('.box').should('not.contain','Member Import')
	   cy.get('.box').should('not.contain','Back Up Database')
	   cy.get('.box').should('not.contain','SQL Manager')
	   cy.get('.box').should('not.contain','Query Form')

	})



	it.skip('cleans for reruns', () =>{
		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(6) input').click();


	   cy.get('select').select('Delete')
	   cy.pause()
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




}) //Contesxt


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
