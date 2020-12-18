import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Test Member roles Web access ', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('Test')
		cy.addMembers('Test', 1)
		cy.logout()
	})

	it('Let Test Role access CP', () => {

	   cy.authVisit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('Test').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP
	})


	it('Turns website offline --> Members cannot view Site but Super Aamin can', () =>{

	   cy.authVisit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('admin')//ensure admin logged in


	   cy.get('.ee-sidebar').contains('Settings').click()


		cy.get('.on > .slider').click();
		cy.get('input').contains('Save Settings').click()

		cy.visit('/')

		cy.logout()

		cy.visit('admin.php?/cp/login');
	  	cy.get('#username').type('Test1');
		cy.get('#password').type('password');
		cy.get('.button').click();



		cy.visit('/',{failOnStatusCode:false})

	   cy.on('uncaught:exception', (err, runnable) => {
			    expect(err.message).to.include('something about the error')
			    done()
			    return false
		})
		/*got this block off of cypress docs it allows for you to continue
	   if there is an error which is what trying to access the website while offline will do*/
	   cy.contains('This site is currently offline')
	})

	it('Super Admins can allow roles to access offline site', () => {
		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('Test').click()
	   cy.get('button').contains('Website Access').click()

		cy.get('#fieldset-website_access .checkbox-label:nth-child(2) > input').click(); //Turn offline access on for members
		cy.get('button').contains('Save').click()

		logout()

		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('Test1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('/',{failOnStatusCode: false})

	})

	it.skip('cleans for reruns', () => {
		//Turn web back on
		//Turn member access off.

		//Turn site online
		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('admin')//ensure admin logged in


	   cy.get('.ee-sidebar').contains('Settings').click()


		cy.get('#fieldset-is_system_on > .field-control > .toggle-btn').click()
		cy.get('input').contains('Save Settings').click()

		cy.visit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('Test').click()
	   cy.get('button').contains('Website Access').click()

		cy.get('#fieldset-website_access .checkbox-label:nth-child(2) > input').click(); //Turn offline access on for members
		cy.get('button').contains('Save').click()
	})

})//End Context

function logout(){
  cy.visit('admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}

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
