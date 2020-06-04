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
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

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
	   
	 
	})

	it('Checks all Group Access', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()
		cy.get('#fieldset-template_group_access .checkbox--small').click();		
		cy.get('button').contains('Save').eq(0).click()

		logout()

	})


	it('Can see CP and Temp but cannot delete yet', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').should('not.contain', 'No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')

	   cy.get('.box').contains('about')
	   cy.get('.box').contains('blog')
	   cy.get('.box').contains('common')
	   cy.get('.box').contains('contact')
	   cy.get('.box').contains('home')
	   cy.get('.box').contains('layouts')

	   cy.get('.box').contains('about').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')
	})


	it('Can delete after being allowed to; but only in about', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('TempManager').click()
	   cy.get('button').contains('CP Access').click()


		cy.get('#fieldset-template_group_access .nestable-item:nth-child(1) .nestable-item:nth-child(1) input').click();
		cy.get('#fieldset-template_group_access .nestable-item:nth-child(1) .nestable-item:nth-child(2) input').click();
		cy.get('#fieldset-template_group_access .nestable-item:nth-child(1) .nestable-item:nth-child(3) input').click();
		cy.get('#fieldset-template_group_access .nestable-item:nth-child(1) .nestable-item:nth-child(4) input').click();
			//allow all things for about but nothing else
		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').should('not.contain', 'No Template Groups found')
	   cy.get('.box').contains('Messages')
	   cy.get('.box').contains('Email')
	   cy.get('.box').contains('Template Routes')

	   cy.get('.box').contains('about')
	   cy.get('.box').contains('blog')
	   cy.get('.box').contains('common')
	   cy.get('.box').contains('contact')
	   cy.get('.box').contains('home')
	   cy.get('.box').contains('layouts')

	   cy.get('.box').contains('about').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',3)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')
		cy.get('select').select('Delete')


		cy.get('.box').contains('blog').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')

		cy.get('.box').contains('common').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')


		cy.get('.box').contains('contact').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')


		cy.get('.box').contains('home').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')

		cy.get('.box').contains('layouts').click()

		cy.get('.check-ctrl > input').click();
		cy.get('select').find('option').should('have.length',2)
		cy.get('select').select('-- with selected --')
		cy.get('select').select('Export Templates')
	})

	it('can edit about but not the other templates', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').contains('about').click()
		cy.get('.edit:nth-child(2) > a').should('exist');

		 cy.get('.box').contains('blog').click()
		cy.get('.edit:nth-child(2) > a').should('not.exist');

		cy.get('.box').contains('common').click()
		cy.get('.edit:nth-child(2) > a').should('not.exist');

		cy.get('.box').contains('contact').click()
		cy.get('.edit:nth-child(2) > a').should('not.exist');

		cy.get('.box').contains('home').click()
		cy.get('.edit:nth-child(2) > a').should('not.exist');

		cy.get('.box').contains('layouts').click()
		cy.get('.edit:nth-child(2) > a').should('not.exist');


		cy.get('.box').contains('about').click()
		cy.get('.edit:nth-child(2) > a').should('exist');
		cy.get('.edit:nth-child(2) > a').click();
		cy.get('h1').contains('Edit Template')


	})


	it('can change settings in about but not the other templates', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('TempManager1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('TempManager1')
		cy.get('.main-nav__account-icon > img').click()
		cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()


		cy.get('.ee-sidebar').contains('Developer').should('exist')
		cy.get('.ee-sidebar').contains('CP Overview')

	   cy.get('.ee-sidebar').should('not.contain','Categories')
	   cy.get('.ee-sidebar').should('not.contain', 'Entries')
	   cy.get('.ee-sidebar').should('not.contain', 'Add-ons')
	   cy.get('.ee-sidebar').should('not.contain','Files')
	   cy.get('.ee-sidebar').should('not.contain','Members')

	   cy.get('.ee-sidebar').contains('Developer').click()
	   cy.get('.ee-sidebar').contains('Templates')

	   cy.get('.ee-sidebar').contains('Templates').click()

	   cy.get('.box').contains('about').click()
		cy.get('.m-link').should('exist');

		 cy.get('.box').contains('blog').click()
		cy.get('.m-link').should('not.exist');

		cy.get('.box').contains('common').click()
		cy.get('.m-link').should('not.exist');

		cy.get('.box').contains('contact').click()
		cy.get('.m-link').should('not.exist');

		cy.get('.box').contains('home').click()
		cy.get('.m-link').should('not.exist');

		cy.get('.box').contains('layouts').click()
		cy.get('.m-link').should('not.exist');


		cy.get('.box').contains('about').click()
		cy.get('.m-link').should('exist');
		cy.get('.m-link').click();
		cy.wait(1000)
		cy.get('h1').contains('Template Settings')


	})

	it('cleans for reruns', () => {
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