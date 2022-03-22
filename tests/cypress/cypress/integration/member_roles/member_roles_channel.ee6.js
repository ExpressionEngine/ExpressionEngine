import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Channels ', () => {

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





   cy.get('body').type('{ctrl}', {release: false}).type('s')
 })

 it('Ensure Channel Manager can add and view channels', () => {
  cy.auth({
    email: 'Channel1',
    password: 'password'
  })
   cy.visit('admin.php?/cp/members/profile/settings')

   cy.get('h1').contains('Channel1')
   cy.get('.main-nav__account-icon > img').click()

   cy.get('.ee-sidebar').contains('Categories')
   cy.get('.ee-sidebar').should('not.contain','Entries')

   cy.get('.ee-sidebar').should('not.contain','Files')
   cy.get('.ee-sidebar').should('not.contain','Members')
   cy.get('.ee-sidebar').should('not.contain','Add-Ons')

    cy.visit('admin.php?/cp/channels')
    cy.hasNoErrors()
    cy.get('a').contains('New Channel').should('exist')
    cy.get('a').contains('New Channel').click()
    cy.hasNoErrors()
 })



 it.skip('cleans for reruns', () => {
   cy.auth()

   cy.visit('admin.php?/cp/members/roles')

    cy.get('.list-item:nth-child(2) input').click();
    cy.pause()

    cy.get('select').select('Delete')
    cy.get('.bulk-action-bar > .button').click()
    cy.get('.modal-confirm-delete > .modal > form > .dialog__actions > .dialog__buttons > .button-group > .button').click()


    cy.visit('admin.php?/cp/members')


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
