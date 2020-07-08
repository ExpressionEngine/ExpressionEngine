import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Members ', () => {

	it('Creates Member Manager Role', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
		cy.get('a').contains('New Role').click()
		cy.get('input[name="name"]').clear().type('MemberManager')
		cy.get('button').contains('Save & Close').eq(0).click()

	})

	it('adds a Member Manager member', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();
		add_members('MemberManager',1)
	})

	it('Member Manager can not login because cp access has not been given yet',() => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Members Role access Members and CP', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('MemberManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP
	   cy.get('fieldset[id="fieldset-can_access_members"]').find('button').click() // turns on accessing members



		cy.get('#fieldset-member_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(4) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(5) > input').click();
		cy.get('#fieldset-member_actions .checkbox-label:nth-child(6) > input').click(); //lets role do everthing to members
		cy.get('button').contains('save').eq(0).click()
	})

	it('Cannot add members to "locked" groups (Super admins only)', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members')
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('fieldset[id="fieldset-role_id"]').find('div').contains('Super Admin').click()
	   cy.wait(1500) //takes a second for error to show up
	   cy.get('em').contains('invalid_role_id')
	})


	//Error reported 5/28/2020 will keep the test for when bug is removed
	it('Cannot add members to "locked" groups using additional permissions', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members')
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('div[class="field-inputs lots-of-checkboxes__items"]').find('div').contains('Super Admin').click()
	   
	   cy.wait(1500) //takes a second for error to show up
	   cy.get('em').contains('invalid_role_id')
	})

	it('Can delete members in unlocked groups', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members')
		cy.get(':nth-child(8) > :nth-child(5) > input').click();
		cy.get('select').should('exist')

	})

	it('Cannot access member roles before it is assigned to that', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles',{failOnStatusCode:false})
	   
	   cy.on('uncaught:exception', (err, runnable) => {
			    expect(err.message).to.include('something about the error')
			    done()		    
			    return false
		}) //got this block off of cypress docs
	   cy.contains('You are not authorized')
	})

	it('Can accecss member roles after it is assigned',() =>{
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('MemberManager').click()

	   cy.get('button').contains('CP Access').click()


		cy.get('#fieldset-can_admin_roles .toggle-btn').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(3) > input').click();
		cy.get('.title-bar__extra-tools > .btn:nth-child(3)').click();
		logout()
		

	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('MemberManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('a').contains('New Role').should('exist')
	   cy.get('.ctrl-all').click()
	   cy.get('select').should('exist')
	   cy.get('select').select('Delete')
	   cy.get('select').find('option').should('have.length',2)
	})

	it('cleans for reruns', () => {
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
      member.get('save_and_new_button').click()
  }
}

function logout(){
  cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}

