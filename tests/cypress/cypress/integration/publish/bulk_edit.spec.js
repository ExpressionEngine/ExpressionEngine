/// <reference types="Cypress" />

import BulkEdit from '../../elements/pages/publish/BulkEdit';
import Publish from '../../elements/pages/publish/Publish';
import EntryManager from '../../elements/pages/publish/EntryManager';

const bulk_edit = new BulkEdit;
const entry_manager = new EntryManager;

context('Bulk Edit', () => {

  beforeEach(function(){
    cy.auth();

    entry_manager.load()
    // Sort by title to normalize sorting since date sort might be inconsistent
    // across environments since entries have the same entry date
    entry_manager.get('sort_links').eq(1).click()
    cy.hasNoErrors()

  })

  it('should load the bulk edit modal', () => {
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')
    cy.hasNoErrors()

    bulk_edit.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Editing 3 entries') })
    bulk_edit.get('filter_heading').invoke('text').then((text) => { expect(text).to.be.equal('3 Selected Entries') })

    bulk_edit.get('selected_entries').find('h2').then(function($li) {
      let selected_entries = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })

      expect(selected_entries).to.deep.equal(['Band Title',
      'Getting to Know ExpressionEngine', 'Welcome to the Example Site!'])
    })

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')

    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })

      expect(field_options).to.deep.equal(['Status', 'Expiration date',
      'Make entry sticky?', 'Author', 'Allow comments?', 'Comment expiration date',  'Categories'])
    })

    bulk_edit.get('fluid_fields').should('have.length', 0)
  })

  it('should not make categories or comment settings available if entries do not share them', () => {
    cy.server()

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })
      expect(field_options).to.deep.equal(['Status', 'Expiration date',
      'Make entry sticky?', 'Author'])
    })

    cy.route("GET", "**cp/publish/bulk-edit**").as("ajax");
    bulk_edit.get('selected_entries').eq(0).find('a').click()
    
  })

  it('should filter and manage the selected entries', () => {
    cy.server()

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Howard')
    entry_manager.check_entry('Jason')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('filter_heading').invoke('text').then((text) => { expect(text).to.be.equal('4 Selected Entries') })
    bulk_edit.get('selected_entries_note').contains('Showing 4 of 4')
    bulk_edit.get('selected_entries').should('have.length', 4)

    bulk_edit.get('filter_input').clear().type('about')
    bulk_edit.get('filter_heading').invoke('text').then((text) => { expect(text).to.be.equal('4 Selected Entries') })
    bulk_edit.get('selected_entries_note').contains('Showing 1 of 4')
    bulk_edit.get('selected_entries').should('have.length', 1)

    cy.route("GET", "**/cp/publish/bulk-edit**").as("ajax");
    bulk_edit.get('selected_entries').eq(0).find('a').click()
    cy.wait('@ajax')
    bulk_edit.get('filter_heading').invoke('text').then((text) => { expect(text).to.be.equal('3 Selected Entries') })
    bulk_edit.get('selected_entries_note').contains('Showing 0 of 3')
    bulk_edit.get('selected_entries').should('have.length', 1)
    bulk_edit.get('selected_entries').eq(0).contains('No entries found.')

    bulk_edit.get('filter_input').clear()
    bulk_edit.get('selected_entries_note').contains('Showing 3 of 3')
    bulk_edit.get('selected_entries').find('h2').then(function($li) {
      let selected_entries = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })

      expect(selected_entries).to.deep.equal(['Band Title', 'Howard', 'Jason'])
    })

    bulk_edit.get('clear_all_link').click()

    entry_manager.get('center_modal').should('not.be.visible')
  })

  it('should manage the fields dropdown based on chosen fields and filter', () => {
    entry_manager.check_entry('About the Label')
    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('add_field').should('exist')

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Status")').click()

    // This channel has comments disabled
    const expected_fields = ['Expiration date', 'Make entry sticky?', 'Author', 'Categories']

    // Status should be removed from available options
    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })
      expect(field_options).to.deep.equal(expected_fields)
    })

    bulk_edit.get('field_options_filter').clear().type('Status')
    bulk_edit.get('field_options').should('have.length', 0)

    // Status should not be added back when filter is cleared
    bulk_edit.get('field_options_filter').clear()
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text();
      })
      expect(field_options).to.deep.equal(expected_fields)
    })
  })

  it('should change the status on the selected entries', () => {
   // entry_manager.get_row_for_title('About the Label').not().contains('CLOSED', { matchCase: false })
   // entry_manager.get_row_for_title('Band Title').not().contains('CLOSED', { matchCase: false })
   // entry_manager.get_row_for_title('Chloe').not().contains('CLOSED', { matchCase: false })

   entry_manager.get_row_for_title('About the Label').should('not.contain', 'CLOSED')
   entry_manager.get_row_for_title('Band Title').should('not.contain', 'CLOSED')
   entry_manager.get_row_for_title('Chloe').should('not.contain', 'CLOSED')

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Chloe')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Editing 3 entries') })

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Status")').click()

    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0).find('input[value=closed]').click()
    bulk_edit.get('save_all_button').click()

    entry_manager.get('center_modal').should('not.be.visible')
    entry_manager.get_row_for_title('About the Label').contains('CLOSED', { matchCase: false })
    entry_manager.get_row_for_title('Band Title').contains('CLOSED', { matchCase: false })
    entry_manager.get_row_for_title('Chloe').contains('CLOSED', { matchCase: false })
    entry_manager.get_row_for_title('Howard').contains('OPEN', { matchCase: false })
  })

  it('should change all the things on the selected entries', () => {
    cy.server()
    cy.route("GET", "**/cp/publish/bulk-edit**").as("ajax");

    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Status")').click()
    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0).find('input[value="closed"]').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Expiration date")').click()
    bulk_edit.get('fluid_fields').eq(1).find('input[name=expiration_date]').clear().type('2/14/2018 4:00 PM')
    bulk_edit.get('fluid_fields').eq(1).click() // Close date picker

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Comment expiration date")').click()
    bulk_edit.get('fluid_fields').eq(2).find('input[name=comment_expiration_date]').clear().type('2/14/2018 5:00 PM')
    bulk_edit.get('fluid_fields').eq(2).click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Make entry sticky?")').click()
    bulk_edit.get('fluid_fields').eq(3).find('a.toggle-btn').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Allow comments?")').click()
    bulk_edit.get('fluid_fields').eq(4).find('a.toggle-btn').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Author")').click()
    bulk_edit.get('fluid_fields').eq(5).find('input[value="2"]').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Categories")').click()
    bulk_edit.get('fluid_fields').eq(6).find('input[value="2"]').click()

    // Make sure fields retain values after removing an entry!
    bulk_edit.get('selected_entries').eq(0).find('a').click()
    cy.wait('@ajax')
    cy.wait(1000) //wait 1 sec

    bulk_edit.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Editing 2 entries') })

    bulk_edit.get('fluid_fields').eq(0).find('input[value="closed"]').should('be.checked')
    bulk_edit.get('fluid_fields').eq(1).find('input[name=expiration_date]').should('have.value', '2/14/2018 4:00 PM')
    bulk_edit.get('fluid_fields').eq(2).find('input[name=comment_expiration_date]').should('have.value', '2/14/2018 5:00 PM')
    bulk_edit.get('fluid_fields').eq(3).find('a.toggle-btn').should('have.class', 'on')
    bulk_edit.get('fluid_fields').eq(4).find('a.toggle-btn').should('have.class', 'on')
    bulk_edit.get('fluid_fields').eq(5).find('input[value="2"]:visible').should('be.checked')
    bulk_edit.get('fluid_fields').eq(6).find('input[value="1"]:visible').should('not.be.checked')
    bulk_edit.get('fluid_fields').eq(6).find('input[value="2"]:visible').should('be.checked')

    bulk_edit.get('save_all_button').click()
    entry_manager.get('alert_success').should('exist')

    const entries = ['Getting to Know ExpressionEngine', 'Welcome to the Example Site!']

    entries.forEach(function(entry, index) {
      entry_manager.load()
      entry_manager.click_edit_for_entry(entry)

      const publish = new Publish
      publish.get('tab_links').eq(1).click() // Date tab
      publish.get('wrap').find('input[name=expiration_date]').should('have.value', '2/14/2018 4:00 PM')
      publish.get('wrap').find('input[name=comment_expiration_date]').should('have.value', '2/14/2018 5:00 PM')
      publish.get('tab_links').eq(2).click() // Categories tab
      publish.get('wrap').find('input[value="1"]:visible').should('not.be.checked')
      publish.get('wrap').find('input[value="2"]:visible').should('be.checked')
      publish.get('tab_links').eq(3).click() // Options tab
      publish.get('wrap').find('input[value="closed"]:visible').should('be.checked')
      publish.get('wrap').find('a.toggle-btn[data-toggle-for="sticky"]').should('have.class', 'on')
      publish.get('wrap').find('a.toggle-btn[data-toggle-for="allow_comments"]').should('have.class', 'on')
    })

  })

})
