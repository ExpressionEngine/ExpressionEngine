import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Addons ', () => {

	it('Creates Addon Manager Role', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
		cy.get('a').contains('New Role').click()
		cy.get('input[name="name"]').clear().type('AddonManager')
		cy.get('button').contains('Save & Close').eq(0).click()

	})

	it('adds a Addon Manager member', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
		cy.get('#username').type('admin');
		cy.get('#password').type('password');
		cy.get('.button').click();
		add_members('AddonManager',1)
	})

	it('Addon Manager can not login because cp access has not been given yet',() => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('AddonManager1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let Addon Role access Addons and CP', () => {
	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('AddonManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_addons .toggle-btn').click();
		cy.get('#fieldset-can_admin_addons .toggle-btn').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(4) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(5) > input').click();
		cy.get('#fieldset-addons_access .checkbox-label:nth-child(6) > input').click();
		cy.get('#fieldset-rte_toolsets .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-rte_toolsets .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-rte_toolsets .checkbox-label:nth-child(3) > input').click(); //lets them do anything with addons

	   

		cy.get('button').contains('save').eq(0).click()
	})

	it('Can see the Addons now but nothing else',() => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('AddonManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('AddonManager1')
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Add-Ons').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')

	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')
	  
	})

	it('Can Access all Addons and has the option to Uninstall them',() => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('AddonManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('AddonManager1')
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()

	   cy.get('.ee-sidebar').contains('Add-Ons').click()

	   cy.contains('Email')
	   cy.contains('Rich Text Editor')
	   cy.contains('Statistics')
	   

	   cy.get(':nth-child(1) > .add-on-card__cog > .fas').click()
	   cy.get('a').contains('Uninstall')

	   cy.get('.main-nav__title > h1').click()

	   cy.get(':nth-child(2) > .add-on-card__cog > .fas').click()
	   cy.get('a').contains('Uninstall')

	   cy.get('.main-nav__title > h1').click()

	   cy.get(':nth-child(3) > .add-on-card__cog > .fas').click()
	   cy.get('a').contains('Uninstall')

	})

	it('Can not access Email after that permission is taken off', () =>{

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();


	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('AddonManager').click()

	   cy.get('button').contains('CP Access').click()
		
	   cy.get('#fieldset-addons_access .checkbox-label:nth-child(6) > input').click(); //turn off email
	   cy.get('button').contains('Save').click()

	   logout()

	   cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('AddonManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('AddonManager1')
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()

	   cy.get('.ee-sidebar').contains('Add-Ons').click()

	   
	   cy.contains('Rich Text Editor')
	   cy.contains('Statistics')
	   cy.get(':nth-child(3) > .add-on-card__cog > .fas').should('not.exist')

	})



	it('Cleans for reruns', () =>{
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

	   cy.get('.list-item:nth-child(1) input').click();


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


}) //End Context

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