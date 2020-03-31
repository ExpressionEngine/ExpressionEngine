/// <reference types="Cypress" />

import BulkEdit from '../../elements/pages/publish/BulkEdit';
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
    /*entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select('Bulk Edit')
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')
    cy.hasNoErrors()

    bulk_edit.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Editing 3 entries') })
    bulk_edit.get('filter_heading').then((text) => { expect(text).to.be.equal('3 Selected Entries') })

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

    bulk_edit.get('fluid_fields').should('have.length', 0)*/
  })
/*
  it('should not make categories or comment settings available if entries do not share them', () => {
    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')

    entry_manager.get('bulk_action').select 'Bulk Edit'
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Make entry sticky?', 'Author']

    bulk_edit.get('selected_entries').eq(0].find('a').trigger 'click' // Click method not working
    wait_for_ajax
    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Make entry sticky?', 'Author', 'Allow comments?', 'Comment expiration date',  'Categories']
  }

  it('should filter and manage the selected entries', () => {
    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Howard')
    entry_manager.check_entry('Jason')

    entry_manager.get('bulk_action').select 'Bulk Edit'
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('filter_heading').then((text) => { expect(text).to.be.equal('4 Selected Entries'
    bulk_edit.get('selected_entries_note').contains('Showing 4 of 4'
    bulk_edit.should have(4).selected_entries

    bulk_edit.get('filter_input').clear().type('about'
    bulk_edit.get('filter_heading').then((text) => { expect(text).to.be.equal('4 Selected Entries'
    bulk_edit.get('selected_entries_note').contains('Showing 1 of 4'
    bulk_edit.should have(1).selected_entries

    bulk_edit.get('selected_entries').eq(0].find('a').trigger 'click'
    wait_for_ajax
    bulk_edit.get('filter_heading').then((text) => { expect(text).to.be.equal('3 Selected Entries'
    bulk_edit.get('selected_entries_note').contains('Showing 0 of 3'
    bulk_edit.should have(1).selected_entries
    bulk_edit.get('selected_entries').eq(0].contains('No entries found.'

    bulk_edit.get('filter_input').clear().type(''
    bulk_edit.get('selected_entries_note').contains('Showing 3 of 3'
    bulk_edit.selected_entries.map {|option| option.find('h2').text}.should == ['Band Title', 'Howard', 'Jason']

    bulk_edit.clear_all_link.trigger 'click'

    entry_manager.has_center_modal?.should == false
  }

  it('should manage the fields dropdown based on chosen fields and filter', () => {
    entry_manager.check_entry('About the Label')
    entry_manager.get('bulk_action').select 'Bulk Edit'
    entry_manager.get('action_submit_button').click()
    bulk_edit.wait_for_add_field

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Status'

    // This channel has comments disabled
    expected_fields = ['Expiration date', 'Make entry sticky?', 'Author', 'Categories']

    // Status should be removed from available options
    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.field_options.map {|option| option.text}.should == expected_fields

    bulk_edit.get('field_options_filter').clear().type('Status'
    bulk_edit.should have(0).field_options

    // Status should not be added back when filter is cleared
    bulk_edit.get('field_options_filter').clear().type(''
    bulk_edit.field_options.map {|option| option.text}.should == expected_fields
  }

  it('should change the status on the selected entries', () => {
    entry_manager.get_row_for_title('About the Label').text.should_not include 'CLOSED'
    entry_manager.get_row_for_title('Band Title').text.should_not include 'CLOSED'
    entry_manager.get_row_for_title('Chloe').text.should_not include 'CLOSED'

    entry_manager.check_entry('About the Label')
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Chloe')

    entry_manager.get('bulk_action').select 'Bulk Edit'
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('heading').then((text) => { expect(text).to.be.equal('Editing 3 entries'

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Status'

    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0].find('input[value=closed]').click()
    bulk_edit.save_all_button.click()

    entry_manager.wait_until_center_modal_invisible
    entry_manager.get_row_for_title('About the Label').contains('CLOSED'
    entry_manager.get_row_for_title('Band Title').contains('CLOSED'
    entry_manager.get_row_for_title('Chloe').contains('CLOSED'
    entry_manager.get_row_for_title('Howard').contains('OPEN'
  }

  it('should change all the things on the selected entries', () => {
    entry_manager.check_entry('Band Title')
    entry_manager.check_entry('Getting to Know ExpressionEngine')
    entry_manager.check_entry('Welcome to the Example Site!')

    entry_manager.get('bulk_action').select 'Bulk Edit'
    entry_manager.get('action_submit_button').click()
    bulk_edit.get('heading').should('exist')

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Status'
    bulk_edit.get('fluid_fields').should('exist')
    bulk_edit.get('fluid_fields').eq(0].find('input[value="closed"]').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Expiration date'
    bulk_edit.get('fluid_fields').eq(1].find('input[name=expiration_date]').clear().type('2/14/2018 4:00 PM'
    bulk_edit.get('fluid_fields').eq(1].click() // Close date picker

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Comment expiration date'
    bulk_edit.get('fluid_fields').eq(2].find('input[name=comment_expiration_date]').clear().type('2/14/2018 5:00 PM'
    bulk_edit.get('fluid_fields').eq(2].click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Make entry sticky?'
    bulk_edit.get('fluid_fields').eq(3].find('a.toggle-btn').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Allow comments?'
    bulk_edit.get('fluid_fields').eq(4].find('a.toggle-btn').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Author'
    bulk_edit.get('fluid_fields').eq(5].find('input[value="2"]').click()

    bulk_edit.get('add_field').click()
    bulk_edit.get('field_options').should('exist')
    bulk_edit.click_link 'Categories'
    bulk_edit.get('fluid_fields').eq(6].find('input[value="2"]').click()

    // Make sure fields retain values after removing an entry!
    bulk_edit.get('selected_entries').eq(0].find('a').trigger 'click'
    wait_for_ajax
    bulk_edit.get('heading').then((text) => { expect(text).to.be.equal('Editing 2 entries'

    bulk_edit.get('fluid_fields').eq(0].find('input[value="closed"]').checked?.should == true
    bulk_edit.get('fluid_fields').eq(1].find('input[name=expiration_date]').value.should == '2/14/2018 4:00 PM'
    bulk_edit.get('fluid_fields').eq(2].find('input[name=comment_expiration_date]').value.should == '2/14/2018 5:00 PM'
    bulk_edit.get('fluid_fields').eq(3].find('a.toggle-btn')[:class].should include 'on'
    bulk_edit.get('fluid_fields').eq(4].find('a.toggle-btn')[:class].should include 'on'
    bulk_edit.get('fluid_fields').eq(5].find('input[value="2"]').checked?.should == true
    bulk_edit.get('fluid_fields').eq(6].find('input[value="1"]').checked?.should == false
    bulk_edit.get('fluid_fields').eq(6].find('input[value="2"]').checked?.should == true

    bulk_edit.save_all_button.click()
    entry_manager.wait_for_alert_success

    ['Getting to Know ExpressionEngine', 'Welcome to the Example Site!'].each do |entry|
      entry_manager.load
      entry_manager.click_edit_for_entry(entry)

      publish = Publish.new
      publish.get('tab_links').eq(1].click() // Date tab
      publish.find('input[name=expiration_date]').value.should == '2/14/2018 4:00 PM'
      publish.find('input[name=comment_expiration_date]').value.should == '2/14/2018 5:00 PM'
      publish.get('tab_links').eq(2].click() // Categories tab
      publish.find('input[value="1"]').checked?.should == false
      publish.find('input[value="2"]').checked?.should == true
      publish.get('tab_links').eq(3].click() // Options tab
      publish.find('input[value="closed"]').checked?.should == true
      publish.find('a.toggle-btn[data-toggle-for="sticky"]')[:class].should include 'on'
      publish.find('a.toggle-btn[data-toggle-for="allow_comments"]')[:class].should include 'on'
    }
  }*/
})
