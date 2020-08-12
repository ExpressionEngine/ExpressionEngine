import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles', () => {

  it('Super Admins can add members to default groups', () => {
  	cy.visit('admin.php?/cp/login');

	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

  	add_members('Members',1)
  	add_members('Guests',1)
  	add_members('Pending',1)
  	add_members('Banned',1)
  })

  it('Default Guests cannot access the control pannel',() => {
  	cy.visit('admin.php?/cp/login');
  	cy.get('#username').type('Guests1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Default Banned cannot access the control pannel',() => {
  	cy.visit('admin.php?/cp/login');
  	cy.get('#username').type('Banned1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.get('p').contains('wrong username or password')
  })

  it('Default Members cannot access the control pannel',() =>{
  	cy.visit('admin.php?/cp/login');
  	cy.get('#username').type('Members1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Super Admins have access to all ee-sidebar options',() =>{
  	cy.visit('admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('admin.php?/cp/members/profile/settings')
	cy.get('h1').contains('admin')

	cy.get('.ee-sidebar').contains('Entries')
	cy.get('.ee-sidebar').contains('Files')
	cy.get('.ee-sidebar').contains('Members')
	cy.get('.ee-sidebar').contains('Categories')
	cy.get('.ee-sidebar').contains('Add-Ons')
  })



})

function createRole(Name){
	cy.visit('admin.php?/cp/members/roles')
  	cy.get('a').contains('New Role').click()
  	cy.get('input[name="name"]').type(Name)
}


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










