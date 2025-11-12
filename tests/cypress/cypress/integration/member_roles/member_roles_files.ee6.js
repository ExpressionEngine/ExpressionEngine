import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / File Permissions ', () => {

	before(function(){
		cy.task('db:seed')
		cy.addRole('FileManager')
		cy.addMembers('FileManager', 1)
		cy.logout()
	})


	it('File Manager can not login because cp access has not been given yet',() => {
		cy.auth({
			email: 'FileManager1',
			password: 'password'
		})
	   cy.get('p').contains('You are not authorized to perform this action')
	 })

	it('Let File Role access Files and CP', () => {
	   cy.auth();

	   cy.visit('admin.php?/cp/members/roles')

	   cy.get('div[class="list-item__title"]').contains('FileManager').click()

	   cy.get('button').contains('CP Access').click()
	   cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP


		cy.get('#fieldset-can_access_files .toggle-btn').click();
		cy.get('#fieldset-upload_destination_access .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-upload_destination_access .checkbox-label:nth-child(2) > input').click();

		cy.get('#fieldset-files .checkbox-label:nth-child(1) > input').click();
		cy.get('#fieldset-files .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-files .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-file_upload_directories .checkbox-label:nth-child(3) > input').click();
		cy.get('#fieldset-file_upload_directories .checkbox-label:nth-child(2) > input').click();
		cy.get('#fieldset-file_upload_directories .checkbox-label:nth-child(1) > input').click();

	   cy.get('button').contains('save').eq(0).click()
	})

	it('can login now and can view files but nothing else', () => {
		cy.auth({
			email: 'FileManager1',
			password: 'password'
		})
		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('FileManager1')

		cy.get('.ee-sidebar').contains('Files')

		cy.get('.ee-sidebar').should('not.contain','Entries')
		cy.get('.ee-sidebar').should('not.contain','Members')
		cy.get('.ee-sidebar').should('not.contain','Categories')
		cy.get('.ee-sidebar').should('not.contain','Add-Ons')
	})

	it('Can navigate to the files section',() => {
		cy.auth({
			email: 'FileManager1',
			password: 'password'
		})

		cy.visit('admin.php?/cp/members/profile/settings')
		cy.get('h1').contains('FileManager1')
		cy.get('.ee-sidebar').contains('Files').click()

		cy.hasNoErrors()
	})

}) // context closer
