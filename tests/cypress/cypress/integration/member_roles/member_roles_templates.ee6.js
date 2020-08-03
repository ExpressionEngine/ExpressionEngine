import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Templates roles ', () => {

	it('Creates Templates Manager Role', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
		cy.get('a').contains('New Role').click()
		cy.get('input[name="name"]').clear().type('TempManager')
		cy.get('button').contains('Save & Close').eq(0).click()

	})

	it('adds a Temp Manager member', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();
		add_members('TempManager',1)
	})

	it('Temp Manager can not login because cp access has not been given yet',() => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('TempManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Temp Role access Temp and CP', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('TempManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_design .toggle-btn').click();
		cy.get('#fieldset-can_admin_design .toggle-btn').click();

		cy.get('button').contains('Save').eq(0).click()
	})

	it('Can see CP and Temp', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').contains('No Template Groups found')
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
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()


	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(1) > input').click();
	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(2) > input').click();
	cy.get('#fieldset-template_group_permissions .checkbox-label:nth-child(3) > input').click();
 //turn off security & privacy
		cy.get('button').contains('Save').eq(0).click()

		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		


		cy.get('.ee-sidebar').contains('Developer').should('exist')


	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

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
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()



		cy.get('#fieldset-template_partials .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-template_partials .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-template_partials .checkbox-label:nth-child(3) > input').click();
		
		cy.get('button').contains('Save').eq(0).click()

		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		


		cy.get('.ee-sidebar').contains('Developer').should('exist')
	

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').contains('No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')
	   cy.get('.box').contains('Template Partials')
	})


	it('can turn on variables', () =>{
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()



		cy.get('#fieldset-template_variables .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-template_variables .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-template_variables .checkbox-label:nth-child(3) > input').click();

		
		cy.get('button').contains('Save').eq(0).click()

		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		

		cy.get('.ee-sidebar').contains('Developer').should('exist')
	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').contains('No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')
	   cy.get('.box').contains('Template Partials')
	   cy.get('.box').contains('Template Variables')
	})

	it.skip('cleans for reruns', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(6) input').click();


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
      member.get('save_and_new_button').click()
  }
}

function logout(){
  cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}