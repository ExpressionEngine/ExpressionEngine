/// <reference types="Cypress" />

import BulkEdit from '../../elements/pages/publish/BulkEdit';
import Publish from '../../elements/pages/publish/Publish';
import EntryManager from '../../elements/pages/publish/EntryManager';
import Channel from '../../elements/pages/channel/Channel';

const bulk_edit = new BulkEdit;
const entry_manager = new EntryManager;
const channel = new Channel;

context('Bulk Edit', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function(){
    cy.auth();

    entry_manager.load()
    // Sort by title to normalize sorting since date sort might be inconsistent
    // across environments since entries have the same entry date
    cy.server()
    cy.route("GET", "**/cp/publish/edit**").as("sort");
    entry_manager.get('sort_links').eq(1).click()
    cy.wait('@sort')
    cy.hasNoErrors()

  })

  it('should load the bulk edit modal', () => {
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    
    cy.hasNoErrors()

    
    bulk_edit.get('filter_heading').invoke('text').then((text) => { expect(text).to.be.equal('3 Selected Entries') })

    bulk_edit.get('selected_entries').find('.list-item__content>div:not(".list-item__secondary")').then(function($li) {
      let selected_entries = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().trim();
      })

      expect(selected_entries).to.deep.equal(['Band Title',
      'Getting to Know ExpressionEngine', 'Welcome to the Example Site!'])
    })

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')

    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().replace('Add ', '').trim();
      })

      expect(field_options).to.deep.equal(['Entry Status', 'Expiration date', 'Author', 'Allow comments?', 'Comment expiration date',  'Categories'])
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
    

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().replace('Add ', '').trim();
      })
      expect(field_options).to.deep.equal(['Entry Status', 'Expiration date', 'Author'])
    })

    cy.route("GET", "**/cp/publish/bulk-edit**").as("ajax");
    bulk_edit.get('selected_entries').eq(0).find('a').click()
    cy.wait('@ajax')
    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().replace('Add ', '').trim();
      })
      expect(field_options).to.deep.equal(['Entry Status', 'Expiration date',
      'Author', 'Allow comments?', 'Comment expiration date',  'Categories'])
    })
  })

  it('should filter and manage the selected entries', () => {
    cy.server()

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Howard')
    entry_manager.check_entry('Jason')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    

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
    bulk_edit.get('selected_entries').eq(0).contains('No Entries found.')

    bulk_edit.get('filter_input').clear()
    bulk_edit.get('selected_entries_note').contains('Showing 3 of 3')
    bulk_edit.get('selected_entries').find('.list-item__content>div:not(".list-item__secondary")').then(function($li) {
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

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Entry Status")').click()

    // This channel has comments disabled
    const expected_fields = ['Expiration date', 'Author', 'Categories']

    // Status should be removed from available options
    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().replace('Add ', '').trim();
      })
      expect(field_options).to.deep.equal(expected_fields)
    })

    // the below does not seem applicable to EE6
    /*
    bulk_edit.get('field_options_filter').clear().type('Status')
    bulk_edit.get('field_options').should('have.length', 0)

    // Status should not be added back when filter is cleared
    bulk_edit.get('field_options_filter').clear()
    bulk_edit.get('field_options').then(function($li) {
      let field_options = Cypress._.map($li, function(el) {
          return Cypress.$(el).text().replace('Add ', '').trim();
      })
      expect(field_options).to.deep.equal(expected_fields)
    })*/
  })

  it('should change the status on the selected entries', () => {
    entry_manager.get_row_for_title('About the Label').should('not.contain', 'Closed')
    entry_manager.get_row_for_title('Band Title').should('not.contain', 'Closed')
    entry_manager.get_row_for_title('Chloe').should('not.contain', 'Closed')

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Chloe')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Entry Status")').click()

    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0).find('div[data-dropdown-react]').click()
    cy.wait(100)
    bulk_edit.get('fluid_fields').eq(0).find('div[data-dropdown-react] .select__dropdown-items span:contains("Closed")').click({force:true})

    //bulk_edit.get('save_all_button').click()
    cy.get('input').contains('Save All & Close').first().click()

    entry_manager.get('center_modal').should('not.be.visible')
    entry_manager.get_row_for_title('About the Label').should('contain', 'Closed')
    entry_manager.get_row_for_title('Band Title').should('contain', 'Closed')
    entry_manager.get_row_for_title('Chloe').should('contain', 'Closed')
    entry_manager.get_row_for_title('Howard').should('contain', 'Open')
  })

  it('should change all the things on the selected entries', () => {
    cy.server()

    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()

    

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Entry Status")').click()
    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0).find('div[data-dropdown-react]').click()
    cy.wait(100)
    bulk_edit.get('fluid_fields').eq(0).find('div[data-dropdown-react] .select__dropdown-items span:contains("Closed")').first().click({force:true})

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Expiration date")').click()
    bulk_edit.get('fluid_fields').eq(1).find('input[type!=hidden][name=expiration_date]').clear().type('2/14/2018 4:00 PM')
    //bulk_edit.get('heading').click() // Close date picker AJ
    cy.get('body').click() 

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Comment expiration date")').click()
    bulk_edit.get('fluid_fields').eq(2).find('input[type!=hidden][name=comment_expiration_date]').clear().type('2/14/2018 5:00 PM')
    //bulk_edit.get('heading').click() // Close date picker
    cy.get('body').click()

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Allow comments?")').click()
    bulk_edit.get('fluid_fields').eq(3).find('.toggle-btn').click()

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Author")').click()
    bulk_edit.get('fluid_fields').eq(4).find('input[value="2"]').click()

    //bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.get('field_options').parent().find('a:contains("Categories")').click()
    bulk_edit.get('fluid_fields').eq(5).find('input[value="2"]').click()

    // Make sure fields retain values after removing an entry!
    cy.route("GET", "**/cp/publish/bulk-edit**").as("ajax");
    bulk_edit.get('selected_entries').eq(0).find('a').click()
    //cy.wait('@ajax')
    cy.wait(5000) // ajax does not work here for some reason - possibly Cypress bug

    //bulk_edit.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Editing 2 entries') })
  
    bulk_edit.get('fluid_fields').eq(0).find('.select__button-label').contains('Closed')
    bulk_edit.get('fluid_fields').eq(1).find('input[type!=hidden][name=expiration_date]').should('have.value', '2/14/2018 4:00 PM')
    bulk_edit.get('fluid_fields').eq(2).find('input[type!=hidden][name=comment_expiration_date]').should('have.value', '2/14/2018 5:00 PM')
    bulk_edit.get('fluid_fields').eq(3).find('.toggle-btn').should('have.class', 'on')
    bulk_edit.get('fluid_fields').eq(4).find('input[value="2"]:visible').should('be.checked')
    bulk_edit.get('fluid_fields').eq(5).find('input[value="1"]:visible').should('not.be.checked')
    bulk_edit.get('fluid_fields').eq(5).find('input[value="2"]:visible').should('be.checked')

    //bulk_edit.get('save_all_button').click()
    cy.get('input').contains('Save All & Close').first().click()
    entry_manager.get('alert_success').should('exist')

    const entries = ['Getting to Know ExpressionEngine', 'Welcome to the Example Site!']

    entries.forEach(function(entry, index) {
      entry_manager.load()
      entry_manager.click_edit_for_entry(entry)

      const publish = new Publish
      publish.get('tab_links').eq(1).click() // Date tab
      publish.get('wrap').find('input[type!=hidden][name=expiration_date]').should('have.value', '2/14/2018 4:00 PM')
      publish.get('wrap').find('input[type!=hidden][name=comment_expiration_date]').should('have.value', '2/14/2018 5:00 PM')
      publish.get('tab_links').eq(2).click() // Categories tab
      publish.get('wrap').find('input[value="1"]:visible').should('not.be.checked')
      publish.get('wrap').find('input[value="2"]:visible').should('be.checked')
      publish.get('tab_links').eq(3).click() // Options tab
      publish.get('wrap').find('[data-input-value="status"] .select__button-label').contains('Closed')
      publish.get('wrap').find('[data-toggle-for="allow_comments"]').should('have.class', 'on')
    })

  })

  it.only('should allow setting sticky on enabled channels', () => {
    channel.load_edit_for_channel(1)
    channel.get('settings_tab').click()
    channel.get('sticky_enabled').click()
    channel.get('save_button').click({force: true})
    cy.contains('Channel Updated')
    
    entry_manager.load();
    entry_manager.check_entry('Jason')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()

    bulk_edit.get('field_options').should('exist')

    bulk_edit.get('field_options').contains('Add Make entry sticky?')

    entry_manager.load()
    
    entry_manager.check_entry('Welcome to the Example Site!')
    entry_manager.check_entry('Jason')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()

    bulk_edit.get('field_options').should('exist')

    bulk_edit.get('field_options').should('not.contain', 'Add Make entry sticky?')
    

  })

})
