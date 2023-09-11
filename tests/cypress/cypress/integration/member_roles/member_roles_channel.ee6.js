import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Member Roles / Channel Permissions ', () => {

  before(function(){
    cy.task('db:seed')
    cy.addRole('Channel')
    cy.addMembers('Channel', 1)
    cy.logout()
	})


 it('Channel Manager can not login because cp access has not been given yet',() => {
  cy.auth({
    email: 'Channel1',
    password: 'password'
  })


   cy.get('p').contains('You are not authorized to perform this action')
 })

 it('Let Channel Role access Categories', () => {
  cy.auth()


   cy.visit('admin.php?/cp/members/roles')

   cy.get('div[class="list-item__title"]').contains('Channel').click()


  cy.get('button').contains('CP Access').click()
  cy.get('#fieldset-can_access_cp .toggle-btn').click(); //access CP

  cy.get('#fieldset-can_admin_channels .toggle-btn').click(); // Access Channel Manager

   cy.get('.js-tab-button:nth-child(3)').click();
   cy.get('#fieldset-channel_permissions .checkbox-label:nth-child(1) > input').click();
   cy.get('#fieldset-channel_permissions .checkbox-label:nth-child(2) > input').click();
   cy.get('#fieldset-channel_permissions .checkbox-label:nth-child(3) > input').click();
   cy.get('#fieldset-channel_field_permissions .checkbox-label:nth-child(1) > input').click();
   cy.get('#fieldset-channel_field_permissions .checkbox-label:nth-child(2) > input').click();
   cy.get('#fieldset-channel_field_permissions .checkbox-label:nth-child(3) > input').click();
   cy.get('#fieldset-channel_category_permissions .checkbox-label:nth-child(1) > input').click();
   cy.get('#fieldset-channel_category_permissions .checkbox-label:nth-child(2) > input').click();
   cy.get('#fieldset-channel_category_permissions .checkbox-label:nth-child(3) > input').click();
   cy.get('#fieldset-channel_status_permissions .checkbox-label:nth-child(1) > input').click();
   cy.get('#fieldset-channel_status_permissions .checkbox-label:nth-child(2) > input').click();
   cy.get('#fieldset-channel_status_permissions .checkbox-label:nth-child(3) > input').click();


  cy.get('#fieldset-channel_access .field-inputs > .nestable-item:nth-child(1) > .checkbox-label > input').click();
  cy.get('#fieldset-channel_access .nestable-item:nth-child(1) .nestable-item:nth-child(1) input').click();
  cy.get('#fieldset-channel_access .nestable-item:nth-child(1) .nestable-item:nth-child(2) input').click();
  cy.get('#fieldset-channel_access .nestable-item:nth-child(1) .nestable-item:nth-child(3) input').click();
  cy.get('#fieldset-channel_access .nestable-item:nth-child(1) .nestable-item:nth-child(4) input').click();
  cy.get('.nestable-item:nth-child(1) .nestable-item:nth-child(5) input').click();
  cy.get('.nestable-item:nth-child(1) .nestable-item:nth-child(6) input').click();





   cy.get('button').contains('Save').click()
 })

 it('Ensure Channel Manager can add and view channels', () => {
  cy.auth({
    email: 'Channel1',
    password: 'password'
  })
   cy.visit('admin.php?/cp/members/profile/settings')

   cy.get('h1').contains('Channel1')
   cy.dismissLicenseAlert()
   cy.get('.main-nav__account-icon > img').click()

   cy.get('.ee-sidebar').contains('Categories')
   cy.get('.ee-sidebar').should('not.contain','Entries')

   cy.get('.ee-sidebar').should('not.contain','Files')
   cy.get('.ee-sidebar').should('not.contain','Members')
   cy.get('.ee-sidebar').should('not.contain','Add-Ons')

    cy.visit('admin.php?/cp/channels')
    cy.hasNoErrors()
    cy.get('a').contains('New Channel').should('exist')
    cy.dismissLicenseAlert()
    cy.get('a').contains('New Channel').click()
    cy.hasNoErrors()
 })

})
