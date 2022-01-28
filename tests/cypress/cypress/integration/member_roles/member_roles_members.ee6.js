import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Members ', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('MemberManager')
		cy.addMembers('MemberManager', 1)

		cy.visit('admin.php?/cp/members/roles')

		cy.get('div[class="list-item__title"]').contains('MemberManager').parent().find('.list-item__secondary').click()

		cy.get('button').contains('CP Access').click()
		cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP
		cy.get('fieldset[id="fieldset-can_access_members"]').find('button').click() // turns on accessing members



		cy.get('#fieldset-member_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(4) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(5) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(6) > input').click(); //lets role do everthing to members
		cy.get('.form-btns-top .saving-options').click()
		cy.get('button').contains('Save & Close').eq(0).click()

		cy.logout()
	})

	it('Check locked/unlocked status', () => {
		cy.auth()
		cy.visit('admin.php?/cp/members/roles')

		cy.get('div[class="list-item__title"]').contains('MemberManager').parents('.list-item').find('.status-wrap .status-tag').contains('Unlocked').should('exist')
		cy.get('div[class="list-item__title"]').contains('MemberManager').parent().find('.list-item__secondary').click()
		cy.get('#fieldset-is_locked [data-toggle-for="is_locked"]').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/members/roles')
		cy.get('div[class="list-item__title"]').contains('MemberManager').parents('.list-item').find('.status-wrap .status-tag').contains('Locked').should('exist')

		cy.get('div[class="list-item__title"]').contains('MemberManager').parent().find('.list-item__secondary').click()
		cy.get('#fieldset-is_locked [data-toggle-for="is_locked"]').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/members/roles')
		cy.get('div[class="list-item__title"]').contains('MemberManager').parents('.list-item').find('.status-wrap .status-tag').contains('Unlocked').should('exist')
	})

	it('Cannot add members to "locked" groups (Super admins only)', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members')
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('fieldset[id="fieldset-role_id"]').filter(':visible').contains('Super Admin').should('not.exist')
	   // cy.wait(1500) //takes a second for error to show up
	   // cy.get('em').contains('invalid_role_id')
	})

	it('Cannot add members to "locked" groups using additional permissions', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members')
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('div').filter(':visible').contains('Super Admin').should('not.exist')

	   // cy.wait(1500) //takes a second for error to show up
	   // cy.get('em').contains('invalid_role_id')
	})

	it('Cannot access member roles before it is assigned to that', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles',{failOnStatusCode:false})

	   cy.on('uncaught:exception', (err, runnable) => {
			    expect(err.message).to.include('something about the error')
			    done()
			    return false
		}) //got this block off of cypress docs
	   cy.contains('You are not authorized')
	})

	it('Can accecss member roles after it is assigned',() =>{
		cy.auth();


		cy.visit('admin.php?/cp/members/roles')

		cy.get('div[class="list-item__title"]').contains('MemberManager').parent().find('.list-item__secondary').click()

		cy.get('button').contains('CP Access').click()


		cy.get('#fieldset-can_admin_roles .toggle-btn').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(3) > input').click();

		cy.logout()


		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('a').contains('New Role').should('exist')
	   cy.get('.ctrl-all').click()
	   cy.get('select').should('exist')
	   cy.get('select').select('Delete')
	   cy.get('select').find('option').should('have.length',2)
	})

	it.skip('cleans for reruns', () => {
	   cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

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




}) // End of context

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

