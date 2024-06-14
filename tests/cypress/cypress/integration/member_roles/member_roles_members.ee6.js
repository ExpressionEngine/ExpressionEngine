import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Members Permissions', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('MemberManager')
		cy.addMembers('MemberManager', 1)

		cy.visit('admin.php?/cp/members/roles')

		cy.get('a.list-item__content:contains("MemberManager")').click()

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

		cy.logout()
	})

	it('Check locked/unlocked status', () => {
		cy.auth()
		cy.visit('admin.php?/cp/members/roles')

		cy.get('a.list-item__content:contains("MemberManager")').parents('.list-item').find('.list-item__secondary').contains('Unlocked').should('exist')
		cy.get('a.list-item__content:contains("MemberManager")').click()
		cy.get('#fieldset-is_locked [data-toggle-for="is_locked"]').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/members/roles')
		cy.get('a.list-item__content:contains("MemberManager")').parents('.list-item').find('.list-item__secondary').contains('Locked').should('exist')

		cy.get('a.list-item__content:contains("MemberManager")').click()
		cy.get('#fieldset-is_locked [data-toggle-for="is_locked"]').click()
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.visit('admin.php?/cp/members/roles')
		cy.get('div[class="list-item__title"]').contains('MemberManager').parents('.list-item').find('.list-item__secondary').contains('Unlocked').should('exist')
	})

	it('Cannot add members to "locked" groups (Super admins only)', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members')
	   cy.dismissLicenseAlert()
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('fieldset[id="fieldset-role_id"]').filter(':visible').contains('Super Admin').should('not.exist')
	   // cy.wait(1500) //takes a second for error to show up
	   // cy.get('em').contains('invalid_role_id')
	})

	it('Cannot add members to "locked" groups using additional permissions', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members')
	   cy.dismissLicenseAlert()
	   cy.get('a').contains('New Member').click()
	   cy.get('button').contains('Roles').click()
	   cy.get('div').filter(':visible').contains('Super Admin').should('not.exist')

	   // cy.wait(1500) //takes a second for error to show up
	   // cy.get('em').contains('invalid_role_id')
	})

	it('Cannot access member roles before it is assigned to that', () => {
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles',{failOnStatusCode:false})

	   cy.on('uncaught:exception', (err, runnable) => {
			    expect(err.message).to.include('something about the error')
			    done()
			    return false
		}) //got this block off of cypress docs
	   cy.contains('You are not authorized')
	})

	it('Cannot access member roles before permissions saved', () => {
		cy.auth();

		cy.visit('admin.php?/cp/members/roles')

		cy.get('a.list-item__content:contains("MemberManager")').click()

		cy.get('button').contains('CP Access').click()

		cy.get('#fieldset-can_admin_roles .toggle-btn').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(3) > input').click();

		cy.logout()
		
		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles',{failOnStatusCode:false})

	   cy.on('uncaught:exception', (err, runnable) => {
			    expect(err.message).to.include('something about the error')
			    done()
			    return false
		}) //got this block off of cypress docs
	   cy.contains('You are not authorized')

	   cy.logout()
	   cy.auth();

		cy.visit('admin.php?/cp/members/roles')

		cy.get('a.list-item__content:contains("MemberManager")').click()

		cy.get('button').contains('CP Access').click()

		cy.get('#fieldset-can_admin_roles .toggle-btn').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(1) > input').should('not.be.checked');
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(2) > input').should('not.be.checked');
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(3) > input').should('not.be.checked');
	})

	it('Can accecss member roles after it is assigned',() =>{
		cy.auth();


		cy.visit('admin.php?/cp/members/roles')

		cy.get('a.list-item__content:contains("MemberManager")').click()

		cy.get('button').contains('CP Access').click()

		cy.get('#fieldset-can_admin_roles .toggle-btn').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-role_actions .checkbox-label:nth-child(3) > input').click();
		cy.get('body').type('{ctrl}', {release: false}).type('s')

		cy.logout()


		cy.auth({
			email: 'MemberManager1',
			password: 'password'
		})

	   cy.visit('admin.php?/cp/members/roles')
	   cy.dismissLicenseAlert()
	   cy.get('a').contains('New Role').should('exist')
	   cy.get('.ctrl-all').click()
	   cy.get('select').should('exist')
	   cy.get('select').select('Delete')
	   cy.get('select').find('option').should('have.length',2)
	})

}) // End of context