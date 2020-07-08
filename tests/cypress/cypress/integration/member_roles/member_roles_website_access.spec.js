import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Test Member roles Web access ', () => {

	it('(Sanity check) : Allows Members to view the Web Site', () => {
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('Members1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
		cy.get('h2').contains('Comment as Members1') //logged in as Member1
	})

	it('Turns website offline --> Members cannot view Site but Super Aamin can', () =>{
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('admin')//ensure admin logged in
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()

	   cy.get('.ee-sidebar').contains('Settings').click()

	   
		cy.get('.on > .slider').click();
		cy.get('input').contains('Save Settings').click()

		cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
		cy.get('h2').contains('Comment as admin') //logged in as admin
		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	  	cy.get('#username').type('Members1');
		cy.get('#password').type('password');
		cy.get('.button').click();

		

		cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments',{failOnStatusCode:false})
	   
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
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();
	   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('Members').click()
	   cy.get('button').contains('Website Access').click()

		cy.get('#fieldset-website_access .checkbox-label:nth-child(2) > input').click(); //Turn offline access on for members
		cy.get('button').contains('Save').click()

		logout()

		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('Members1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
		cy.get('h2').contains('Comment as Members1') //logged in as Member1
	})

	it('cleans for reruns', () => {
		//Turn web back on
		//Turn member access off.

		//Turn site online
		cy.visit('http://localhost:8888/admin.php?/cp/login');
	   cy.get('#username').type('admin');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

	   cy.get('h1').contains('admin')//ensure admin logged in
	   cy.get('.main-nav__account-icon > img').click()
	   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()

	   cy.get('.ee-sidebar').contains('Settings').click()

	   
		cy.get('#fieldset-is_system_on > .field-control > .toggle-btn').click()
		cy.get('input').contains('Save Settings').click()

		cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('Members').click()
	   cy.get('button').contains('Website Access').click()

		cy.get('#fieldset-website_access .checkbox-label:nth-child(2) > input').click(); //Turn offline access on for members
		cy.get('button').contains('Save').click()
	})

})//End Context

function logout(){
  cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
  cy.get('.main-nav__account-icon > img').click()
  cy.get('[href="admin.php?/cp/login/logout"]').click()
}
