require './bootstrap.rb'

feature 'Bulk Edit' do
  before :each do
    cp_session
    @entry_manager = EntryManager.new
    @entry_manager.load
    # Sort by title to normalize sorting since date sort might be inconsistent
    # across environments since entries have the same entry date
    @entry_manager.sort_links[1].click
    no_php_js_errors

    @bulk_edit = BulkEdit.new
  end

  it 'should load the bulk edit modal' do
    @entry_manager.check_entry('Band Title')
    @entry_manager.check_entry('Getting to Know ExpressionEngine')
    @entry_manager.check_entry('Welcome to the Example Site!')

    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_heading
    no_php_js_errors

    @bulk_edit.heading.text.should == 'Editing 3 entries'
    @bulk_edit.filter_heading.text.should == '3 Selected Entries'

    @bulk_edit.selected_entries.map {|option| option.find('h2').text}.should == ['Band Title',
      'Getting to Know ExpressionEngine', 'Welcome to the Example Site!']

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Make entry sticky?', 'Author', 'Allow comments?', 'Comment expiration date',  'Categories']

    @bulk_edit.should have(0).fluid_fields
  end

  it 'should not make categories or comment settings available if entries do not share them' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Band Title')
    @entry_manager.check_entry('Getting to Know ExpressionEngine')

    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_heading

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Make entry sticky?', 'Author']

    @bulk_edit.selected_entries[0].find('a').trigger 'click' # Click method not working
    wait_for_ajax
    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Make entry sticky?', 'Author', 'Allow comments?', 'Comment expiration date',  'Categories']
  end

  it 'should filter and manage the selected entries' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Band Title')
    @entry_manager.check_entry('Howard')
    @entry_manager.check_entry('Jason')

    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_heading

    @bulk_edit.filter_heading.text.should == '4 Selected Entries'
    @bulk_edit.selected_entries_note.text.should include 'Showing 4 of 4'
    @bulk_edit.should have(4).selected_entries

    @bulk_edit.filter_input.set 'about'
    @bulk_edit.filter_heading.text.should == '4 Selected Entries'
    @bulk_edit.selected_entries_note.text.should include 'Showing 1 of 4'
    @bulk_edit.should have(1).selected_entries

    @bulk_edit.selected_entries[0].find('a').trigger 'click'
    wait_for_ajax
    @bulk_edit.filter_heading.text.should == '3 Selected Entries'
    @bulk_edit.selected_entries_note.text.should include 'Showing 0 of 3'
    @bulk_edit.should have(1).selected_entries
    @bulk_edit.selected_entries[0].text.should include 'No entries found.'

    @bulk_edit.filter_input.set ''
    @bulk_edit.selected_entries_note.text.should include 'Showing 3 of 3'
    @bulk_edit.selected_entries.map {|option| option.find('h2').text}.should == ['Band Title', 'Howard', 'Jason']

    @bulk_edit.clear_all_link.trigger 'click'

    @entry_manager.has_center_modal?.should == false
  end

  it 'should manage the fields dropdown based on chosen fields and filter' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_add_field

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Status'

    # This channel has comments disabled
    expected_fields = ['Expiration date', 'Make entry sticky?', 'Author', 'Categories']

    # Status should be removed from available options
    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.field_options.map {|option| option.text}.should == expected_fields

    @bulk_edit.field_options_filter.set 'Status'
    @bulk_edit.should have(0).field_options

    # Status should not be added back when filter is cleared
    @bulk_edit.field_options_filter.set ''
    @bulk_edit.field_options.map {|option| option.text}.should == expected_fields
  end

  it 'should change the status on the selected entries' do
    @entry_manager.get_row_for_title('About the Label').text.should_not include 'CLOSED'
    @entry_manager.get_row_for_title('Band Title').text.should_not include 'CLOSED'
    @entry_manager.get_row_for_title('Chloe').text.should_not include 'CLOSED'

    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Band Title')
    @entry_manager.check_entry('Chloe')

    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_heading

    @bulk_edit.heading.text.should == 'Editing 3 entries'

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Status'

    @bulk_edit.wait_for_fluid_fields
    @bulk_edit.fluid_fields[0].find('input[value=closed]').click
    @bulk_edit.save_all_button.click

    @entry_manager.wait_until_center_modal_invisible
    @entry_manager.get_row_for_title('About the Label').text.should include 'CLOSED'
    @entry_manager.get_row_for_title('Band Title').text.should include 'CLOSED'
    @entry_manager.get_row_for_title('Chloe').text.should include 'CLOSED'
    @entry_manager.get_row_for_title('Howard').text.should include 'OPEN'
  end

  it 'should change all the things on the selected entries' do
    @entry_manager.check_entry('Band Title')
    @entry_manager.check_entry('Getting to Know ExpressionEngine')
    @entry_manager.check_entry('Welcome to the Example Site!')

    @entry_manager.bulk_action.select 'Bulk Edit'
    @entry_manager.action_submit_button.click
    @bulk_edit.wait_for_heading

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Status'
    @bulk_edit.wait_for_fluid_fields
    @bulk_edit.fluid_fields[0].find('input[value="closed"]').click

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Expiration date'
    @bulk_edit.fluid_fields[1].find('input[name=expiration_date]').set '2/14/2018 4:00 PM'
    @bulk_edit.fluid_fields[1].click # Close date picker

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Comment expiration date'
    @bulk_edit.fluid_fields[2].find('input[name=comment_expiration_date]').set '2/14/2018 5:00 PM'
    @bulk_edit.fluid_fields[2].click

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Make entry sticky?'
    @bulk_edit.fluid_fields[3].find('a.toggle-btn').click

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Allow comments?'
    @bulk_edit.fluid_fields[4].find('a.toggle-btn').click

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Author'
    @bulk_edit.fluid_fields[5].find('input[value="2"]').click

    @bulk_edit.add_field.click
    @bulk_edit.wait_for_field_options
    @bulk_edit.add_new_field 'Categories'
    @bulk_edit.fluid_fields[6].find('input[value="2"]').click

    # Make sure fields retain values after removing an entry!
    @bulk_edit.selected_entries[0].find('a').trigger 'click'
    wait_for_ajax
    @bulk_edit.heading.text.should == 'Editing 2 entries'

    @bulk_edit.fluid_fields[0].find('input[value="closed"]').checked?.should == true
    @bulk_edit.fluid_fields[1].find('input[name=expiration_date]').value.should == '2/14/2018 4:00 PM'
    @bulk_edit.fluid_fields[2].find('input[name=comment_expiration_date]').value.should == '2/14/2018 5:00 PM'
    @bulk_edit.fluid_fields[3].find('a.toggle-btn')[:class].should include 'on'
    @bulk_edit.fluid_fields[4].find('a.toggle-btn')[:class].should include 'on'
    @bulk_edit.fluid_fields[5].find('input[value="2"]').checked?.should == true
    @bulk_edit.fluid_fields[6].find('input[value="1"]').checked?.should == false
    @bulk_edit.fluid_fields[6].find('input[value="2"]').checked?.should == true

    @bulk_edit.save_all_button.click
    @entry_manager.wait_for_alert_success

    ['Getting to Know ExpressionEngine', 'Welcome to the Example Site!'].each do |entry|
      @entry_manager.load
      @entry_manager.click_edit_for_entry(entry)

      publish = Publish.new
      publish.tab_links[1].click # Date tab
      publish.find('input[name=expiration_date]').value.should == '2/14/2018 4:00 PM'
      publish.find('input[name=comment_expiration_date]').value.should == '2/14/2018 5:00 PM'
      publish.tab_links[2].click # Categories tab
      publish.find('input[value="1"]').checked?.should == false
      publish.find('input[value="2"]').checked?.should == true
      publish.tab_links[3].click # Options tab
      publish.find('input[value="closed"]').checked?.should == true
      publish.find('a.toggle-btn[data-toggle-for="sticky"]')[:class].should include 'on'
      publish.find('a.toggle-btn[data-toggle-for="allow_comments"]')[:class].should include 'on'
    end
  end
end
