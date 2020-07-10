import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Comments', () => {

  it('Super Admins can add members to default groups', () => {
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
	
	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

  	add_members('Members',3)
  	add_members('Guests',3)
  	add_members('Pending',2)
  	add_members('Banned',2)
  })

  it('Default Guests cannot access the control pannel',() => {
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Guests1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Default Banned cannot access the control pannel',() => {
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Banned1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.get('p').contains('You are not authorized to perform this action')
  })

  it('Default Members can access the control pannel, can access their profile and can toggle view',() =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Members1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
	cy.get('h1').contains('Members1')
	cy.get('.main-nav__account-icon > img').click()
	cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
	cy.get('.ee-sidebar').contains('CP Overview')
	cy.get('.ee-sidebar').should('not.contain','Entries')
	cy.get('.ee-sidebar').should('not.contain','Files')
	cy.get('.ee-sidebar').should('not.contain','Members')
	cy.get('.ee-sidebar').should('not.contain','Categories')
	cy.get('.ee-sidebar').should('not.contain','Add-Ons')
  })

  it('Super Admins have access to all ee-sidebar options',() =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
	cy.get('h1').contains('admin')
	cy.get('.main-nav__account-icon > img').click()
	cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
	
	cy.get('.ee-sidebar').contains('CP Overview')
	cy.get('.ee-sidebar').contains('Entries')
	cy.get('.ee-sidebar').contains('Files')
	cy.get('.ee-sidebar').contains('Members')
	cy.get('.ee-sidebar').contains('Categories')
	cy.get('.ee-sidebar').contains('Add-Ons')
  })

  it('Members cannot comment by default',() =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Members1');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
	cy.get('h2').contains('Comment as Members1')
	cy.get('.w-12 > .required').type('Should not see this comment')
	cy.get('.btn').click()
	cy.contains('You are not allowed to post comments')
  })

  it('Guests cannot comment by default',() =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Members1');
	cy.get('#password').type('password');
	cy.get('.button').click();
	cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
	cy.get('.main-nav__account-icon > img').click()
	cy.get('[href="admin.php?/cp/login/logout"]').click()


	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
	cy.reload()
	cy.get('h2').contains('Comment as a guest')

	cy.get('input[name="name"]').type('Guest')
	cy.get('input[name="email"]').type('guest@test.com')
	cy.get('textarea[name="comment"]').type('Guest').type('Should not see this comment')
	cy.get('.btn').click()
	cy.contains('You are not allowed to post comments')
  })

  it('Admins can allow for Members to comment', () =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/admin.php?/cp/members/roles');
	cy.get('a').contains('Members').click()
	cy.get('button').contains('Website Access').click()

	cy.get('button[data-toggle-for="can_post_comments"]').click()
	cy.get('button[value="save_and_close"]').eq(0).click()
	logout()

	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Members1');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
	cy.get('h2').contains('Comment as Members1')
	cy.get('.w-12 > .required').type('Should see this comment')
	cy.get('.btn').click()
  })

  it('Comment was sent from member',() => {
  	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
  	cy.get('p').contains('Should see this comment')
  })

  it('Comments can be monitored',() => {
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();
  	cy.visit('http://localhost:8888/admin.php?/cp/settings/word-censor');
  	cy.get('.toggle-btn').click()
  	cy.get('input[name="censor_replacement"]').clear().type('*$#@&')
  	cy.get('textarea[name="censored_words"]').clear().type('stupid')
  	cy.get('input[value="Save Settings"]').eq(0).click()
  	cy.get('p').contains('have been saved')

  	logout()

  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('Members2');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
	cy.get('h2').contains('Comment as Members2')
	cy.get('.w-12 > .required').type('This is stupid')
	cy.get('.btn').click()
  })

  it('Comment was sent from member and was monitored',() => {
  	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
  	cy.get('p').contains('This is *$#@&')
  })

  it('Turns censoring off',() =>{
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();
  	cy.visit('http://localhost:8888/admin.php?/cp/settings/word-censor');
  	cy.get('.toggle-btn').click()
  	cy.get('input[name="censor_replacement"]').clear()
  	cy.get('textarea[name="censored_words"]').clear()
  	cy.get('input[value="Save Settings"]').eq(0).click()
  	cy.get('p').contains('have been saved')

  	cy.visit('http://localhost:8888/index.php/blog/entry/action-comedy-how-to#comments')
  	cy.get('p').contains('This is stupid')
  })

  it('Cleans the test for reruns',() => {
  	cy.visit('http://localhost:8888/admin.php?/cp/login');
  	cy.get('#username').type('admin');
	cy.get('#password').type('password');
	cy.get('.button').click();

	cy.visit('http://localhost:8888/admin.php?/cp/members/roles');
	cy.get('a').contains('Members').click()
	cy.get('button').contains('Website Access').click()

	cy.get('button[data-toggle-for="can_post_comments"]').click()
	cy.get('button[value="save_and_close"]').eq(0).click()
	logout()
  })



})

function createRole(Name){
	cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
  	cy.get('a').contains('New Role').click()
  	cy.get('input[name="name"]').type(Name)
}


function logout(){
	cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')
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
	    member.get('save_and_new_button').click()
	}
}










 