require './bootstrap.rb'

feature 'Quick Edit' do
  before :each do
    cp_session
    @entry_manager = EntryManager.new
    @entry_manager.load
    no_php_js_errors

    @quick_edit = QuickEdit.new
  end

  it 'should load the quick edit modal' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Howard')
    @entry_manager.check_entry('Jason')

    @entry_manager.bulk_action.select 'Quick Edit'
    @entry_manager.action_submit_button.click
    @quick_edit.wait_for_heading

    @quick_edit.heading.text.should == 'Editing 3 entries'
    @quick_edit.filter_heading.text.should == '3 Selected Entries'

    @quick_edit.selected_entries.map {|option| option.find('h2').text}.should == ['About the Label', 'Howard', 'Jason']

    @quick_edit.add_field.click
    @quick_edit.wait_for_field_options
    @quick_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Comment expiration date', 'Make entry sticky?', 'Allow comments?', 'Author', 'Categories']

    @quick_edit.should have(0).fluid_fields
  end

  it 'should not make categories available if entries do not share them' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Band Title')

    @entry_manager.bulk_action.select 'Quick Edit'
    @entry_manager.action_submit_button.click
    @quick_edit.wait_for_heading

    @quick_edit.add_field.click
    @quick_edit.wait_for_field_options
    @quick_edit.field_options.map {|option| option.text}.should == ['Status', 'Expiration date',
      'Comment expiration date', 'Make entry sticky?', 'Allow comments?', 'Author']
  end

  it 'should filter and manage the selected entries' do
    @entry_manager.check_entry('About the Label')
    @entry_manager.check_entry('Howard')
    @entry_manager.check_entry('Jason')
    @entry_manager.check_entry('Band Title')

    @entry_manager.bulk_action.select 'Quick Edit'
    @entry_manager.action_submit_button.click
    @quick_edit.wait_for_heading

    @quick_edit.filter_heading.text.should == '4 Selected Entries'
    @quick_edit.selected_entries_note.text.should include 'Showing 4 of 4'
    @quick_edit.should have(4).selected_entries

    @quick_edit.filter_input.set 'about'
    @quick_edit.filter_heading.text.should == '4 Selected Entries'
    @quick_edit.selected_entries_note.text.should include 'Showing 1 of 4'
    @quick_edit.should have(1).selected_entries

    @quick_edit.selected_entries[0].find('a').trigger 'click'
    @quick_edit.filter_heading.text.should == '3 Selected Entries'
    @quick_edit.selected_entries_note.text.should include 'Showing 0 of 3'
    @quick_edit.should have(1).selected_entries
    @quick_edit.selected_entries[0].text.should include 'No entries found.'

    @quick_edit.filter_input.set ''
    @quick_edit.selected_entries_note.text.should include 'Showing 3 of 3'
    @quick_edit.selected_entries.map {|option| option.find('h2').text}.should == ['Band Title', 'Howard', 'Jason']
  end
end
