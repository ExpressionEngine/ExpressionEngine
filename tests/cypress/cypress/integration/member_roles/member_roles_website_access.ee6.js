import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;

context('Member Roles / Frontend access ', () => {

	before(function(){
		cy.task('db:seed')
		cy.eeConfig({item: 'is_system_on', value: 'n'})
		cy.addRole('Test')
		cy.addMembers('Test', 1)

		//Let Test Role access CP
		cy.authVisit('admin.php?/cp/members/roles')
	   cy.get('div[class="list-item__title"]').contains('Test').click()
	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP
	   cy.get('button').contains('Save').first().click()

		cy.logout()
	})

	it('Turns website offline --> Members cannot view Site but Super Admin can', () =>{

		cy.authVisit('/')
		cy.get('body').should('not.contain', 'This site is currently offline')

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

		cy.logout()

		cy.visit('admin.php?/cp/login');
	   cy.get('#username').type('Test1');
	   cy.get('#password').type('password');
	   cy.get('.button').click();

	   cy.visit('/',{failOnStatusCode: false})

	   cy.get('body').should('not.contain', 'This site is currently offline')

	})


})//End Context
