import MemberGroups from '../../elements/pages/members/MemberGroups';
import MemberCreate from '../../elements/pages/members/MemberCreate';
const page = new MemberGroups;
const member = new MemberCreate;


context('Test Member roles Channels ', () => {

 it('Creates Channel Manager', () => {
  cy.visit('http://localhost:8888/admin.php?/cp/login');
  cy.get('#username').type('admin');
  cy.get('#password').type('password');
  cy.get('.button').click();
  
  cy.visit('http://localhost:8888/admin.php?/cp/members/roles')
  cy.get('a').contains('New Role').click()
  cy.get('input[name="name"]').clear().type('Channel')

  
  cy.get('button').contains('Save & Close').eq(0).click()

  })

 it('adds a Channel Manager member', () => {
   cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('admin');
   cy.get('#password').type('password');
   cy.get('.button').click();
   add_members('Channel',1)
 })

 it('Channel Manager can not login because cp access has not been given yet',() => {
   cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('Channel1');
   cy.get('#password').type('password');
   cy.get('.button').click();


   cy.get('p').contains('You are not authorized to perform this action')
 })

 it('Let Channel Role access Categories', () => {
   cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('admin');
   cy.get('#password').type('password');
   cy.get('.button').click();


   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

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

 it('Lets Channel Manager see About Channel on sidebar now Makes sure that they can only see entries assigned to them', () => {
   cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('Channel1');
   cy.get('#password').type('password');
   cy.get('.button').click();
   cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

   cy.get('h1').contains('Channel1')
   cy.get('.main-nav__account-icon > img').click()
   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
   cy.get('.ee-sidebar').contains('CP Overview')
   cy.get('.ee-sidebar').contains('Categories')
   cy.get('.ee-sidebar').contains('Entries')

   cy.get('.ee-sidebar').should('not.contain','Files')
   cy.get('.ee-sidebar').should('not.contain','Members')
  
   cy.get('.ee-sidebar').should('not.contain','Add-Ons')

   cy.get('.ee-sidebar').contains('Entries').click()
   cy.get('div[class="table-responsive table-responsive--collapsible"]').find('tbody').find('tr').should('have.length',3)
   cy.get('.check-ctrl > input').click()
   cy.get('select').select('Delete')
   cy.get('select').select('Edit')
   cy.get('select').select('Bulk Edit')
   cy.get('select').select('Add Categories')
   cy.get('select').select('Remove Categories')
   cy.get('select').find('option').should('have.length', 6)
 })

 it('Can make an entry in the about channel', () => {
  cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('Channel1');
   cy.get('#password').type('password');
   cy.get('.button').click();
   cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

   cy.get('h1').contains('Channel1')
   cy.get('.main-nav__account-icon > img').click()
   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
   
   cy.get('.ee-sidebar').contains('Entries').click()
   cy.get('button').contains('New').click()
   cy.get('a').contains('About').click()
   cy.hasNoErrors()

 })

 it('Cant edit other peoples entries when role is updated',() => {
  cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('admin');
   cy.get('#password').type('password');
   cy.get('.button').click();


   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

   cy.get('div[class="list-item__title"]').contains('Channel').click()


  cy.get('button').contains('CP Access').click()
  cy.get('input[value="can_edit_other_entries_channel_id_1"]').eq(0).click()

  cy.get('button').contains('Save').click()

  logout()

  cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('Channel1');
   cy.get('#password').type('password');
   cy.get('.button').click();
   cy.visit('http://localhost:8888/admin.php?/cp/members/profile/settings')

   cy.get('h1').contains('Channel1')
   cy.get('.main-nav__account-icon > img').click()
   cy.get('[href="admin.php?/cp/homepage/toggle-viewmode"]').click()
  
   cy.get('.ee-sidebar').contains('Entries').click()
   cy.get('div[class="table-responsive table-responsive--collapsible"]').find('tbody').find('tr').should('have.length',1)
 })

 it('cleans for reruns', () => {
   cy.visit('http://localhost:8888/admin.php?/cp/login');
   cy.get('#username').type('admin');
   cy.get('#password').type('password');
   cy.get('.button').click();

   cy.visit('http://localhost:8888/admin.php?/cp/members/roles')

    cy.get('.list-item:nth-child(2) input').click();
    cy.pause()

    cy.get('select').select('Delete')
    cy.get('.bulk-action-bar > .button').click()
    cy.get('.modal-confirm-delete > .modal > form > .dialog__actions > .dialog__buttons > .button-group > .btn').click()
    

    cy.visit('http://localhost:8888/admin.php?/cp/members')


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