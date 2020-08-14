import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles', () => {

	before(function(){
		cy.task('db:seed')
		cy.addMembers('Members', 1)
		cy.addMembers('Guests', 1)
		cy.addMembers('Pending', 1)
		cy.addMembers('Banned', 1)
		cy.logout()
	})


  it('Default Guests cannot access the control pannel',() => {
	cy.auth({
		email: 'Guests1',
		password: 'password'
	})

	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Default Banned cannot access the control pannel',() => {
  	cy.auth({
		email: 'Banned1',
		password: 'password'
	})
	cy.get('p').contains('wrong username or password')
  })

  it('Default Members cannot access the control pannel',() =>{
	cy.auth({
		email: 'Members1',
		password: 'password'
	})
	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Super Admins have access to all ee-sidebar options',() =>{
  	cy.auth();

	cy.visit('admin.php?/cp/members/profile/settings')
	cy.get('h1').contains('admin')

	cy.get('.ee-sidebar').contains('Entries')
	cy.get('.ee-sidebar').contains('Files')
	cy.get('.ee-sidebar').contains('Members')
	cy.get('.ee-sidebar').contains('Categories')
	cy.get('.ee-sidebar').contains('Add-Ons')
  })



})











