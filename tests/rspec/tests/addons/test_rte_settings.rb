require './bootstrap.rb'

def confirm_settings_page (page)
  page.breadcrumb.text.should include 'Add-On Manager'
  page.breadcrumb.text.should include 'Rich Text Editor Configuration'

  page.headings[0].text.should eq 'Rich Text Editor Configuration'
  page.headings[1].text.should eq 'Available Tool Sets'

  page.should have_rte_enabled
  page.should have_rte_enabled_toggle
  page.should have_default_tool_set
  page.should have_save_settings_button
  page.should have_create_new_button
  page.should have_tool_sets

  page.should_not have_tool_set_name
  page.should_not have_choose_tools
end

def confirm_toolset_page (page)
  @page.breadcrumb.text.should include 'Add-On Manager'
  @page.breadcrumb.text.should include 'Rich Text Editor Configuration'
  @page.breadcrumb.text.should include 'RTE Tool Set'

  @page.headings[0].text.should include 'RTE Tool Set'

  @page.should_not have_rte_enabled
  @page.should_not have_rte_enabled_toggle
  @page.should_not have_default_tool_set
  @page.should_not have_create_new_button
  @page.should_not have_tool_sets

  @page.should have_tool_set_name
  @page.should have_choose_tools
  @page.should have_tool_set_save_and_close_button
end

feature 'RTE Settings' do

  before(:each) do
    cp_session

    # Load some extra tool sets for testing purposes
      $db.query(IO.read('support/rte-settings/tool_sets.sql'))
      clear_db_result

    @page = RTESettings.new
    @page.load

    @page.displayed?
    @page.title.text.should eq 'Rich Text Editor'
  end

  before(:each, :stage => 'settings') do
    confirm_settings_page @page
  end

  before(:each, :stage => 'toolset') do
    @page.create_new_button.click
    no_php_js_errors
    @page.displayed?

    confirm_toolset_page @page
  end

  it 'shows the RTE Settings page', :stage => 'settings' do
    @page.rte_enabled.value.should == 'y'
    @page.default_tool_set.has_checked_radio('1').should == true
  end

  it 'can navigate back to the add-on manager via the breadcrumb', :stage => 'settings' do
    @page.breadcrumb.find('a').click
    no_php_js_errors

    addon_manager = AddonManager.new
    addon_manager.displayed?
  end

  it 'can disable & enable the rich text editor', :stage => 'settings' do
    @page.rte_enabled_toggle.click
    @page.save_settings_button.click
    no_php_js_errors

    @page.rte_enabled.value.should == 'n'

    @page.rte_enabled_toggle.click
    @page.save_settings_button.click
    no_php_js_errors

    @page.rte_enabled.value.should == 'y'
  end

  it 'only accepts "y" or "n" for enabled setting', :stage => 'settings' do
    @page.execute_script("$('input[name=rte_enabled]').val('1');")
    @page.save_settings_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error

    @page.execute_script("$('input[name=rte_enabled]').val('yes');")
    @page.save_settings_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
  end

  it 'can change the default tool set', :stage => 'settings' do
    @page.default_tool_set.choose_radio_option('3')
    @page.save_settings_button.click
    no_php_js_errors

    @page.default_tool_set.has_checked_radio('3').should == true
  end

  it 'cannot set a default tool set to an nonexistent tool set', :stage => 'settings' do
    @page.execute_script("$('input[name=\"rte_default_toolset_id\"]:first').val('999');")
    @page.default_tool_set[0].click
    @page.save_settings_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.should have_no_alert_success
  end

  it 'can disable & enable a single tool set', :stage => 'settings' do
    @page.tool_sets[1].text.should include 'Enabled'

    @page.tool_sets[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Disable"
    @page.action_submit_button.click
    no_php_js_errors

    @page.tool_sets[1].text.should include 'Disabled'
    @page.tool_sets[1].text.should_not include 'Enabled'

    @page.tool_sets[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Enable"
    @page.action_submit_button.click
    no_php_js_errors

    @page.tool_sets[1].text.should include 'Enabled'
    @page.tool_sets[1].text.should_not include 'Disabled'
  end

  it 'can disable & enable multiple tool set', :stage => 'settings' do
    @page.text.should include 'Enabled'
    @page.text.should_not include 'Disabled'

    @page.checkbox_header.find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Disable"
    @page.action_submit_button.click
    no_php_js_errors

    @page.text.should include 'Disabled'

    @page.checkbox_header.find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Enable"
    @page.action_submit_button.click
    no_php_js_errors

    @page.text.should include 'Enabled'
    @page.text.should_not include 'Disabled'
  end

  it 'displays an itemized modal when trying to remove 5 or less tool sets', :stage => 'settings' do
    tool_set_name = @page.tool_set_names[0].text

    # Header at 0, first "real" row is 1
    @page.tool_sets[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click

    @page.wait_until_modal_visible
    @page.modal_title.text.should eq "Confirm Removal"
    @page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
    @page.modal.text.should include tool_set_name
    @page.modal.all('.checklist li').length.should eq 1
  end

  it 'displays a bulk confirmation modal when trying to remove more than 5 tool sets', :stage => 'settings' do
    @page.checkbox_header.find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click

    @page.wait_until_modal_visible
    @page.modal_title.text.should eq "Confirm Removal"
    @page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
    @page.modal.text.should include "Tool Set: 6 Tool Sets"
  end

  it 'cannot remove the default tool set', :stage => 'settings' do
    tool_set_name = @page.tool_set_names[1].text

    # This populates the modal with a hidden input so we can modify it later
    @page.tool_sets[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    tool_set_id = @page.tool_sets[2].find('input[type="checkbox"]').value
    @page.execute_script("$('input[name=\"selection[]\"]').val('" + tool_set_id + "');")

    @page.modal_submit_button.click # Submits a form
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_error
    @page.alert.text.should include "The default RTE tool set cannot be removed"
    @page.tool_set_names[1].text.should eq tool_set_name
  end

  it 'can remove a tool set', :stage => 'settings' do
    @page.tool_sets[1].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal_submit_button.click # Submits a form
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include "Tool sets removed"
    @page.alert.text.should include "The following tool sets were removed"
    @page.alert.text.should include "Advanced"
  end

  it 'can bulk remove tool sets', :stage => 'settings' do
    @page.checkbox_header.find('input[type="checkbox"]').set true

    # Uncheck the Default tool set
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Remove"
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal_submit_button.click # Submits a form
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include "Tool sets removed"
    @page.alert.text.should include "The following tool sets were removed"
    @page.alert.text.should include "Advanced"
    @page.alert.text.should include "Even"
    @page.alert.text.should include "Everything"
    @page.alert.text.should include "Lists Only"
    @page.alert.text.should include "and 2 others..."
  end

  it 'can reverse sort tool sets by name', :stage => 'settings' do
    a_to_z_tool_sets = @page.tool_set_names.map {|tool_set| tool_set.text}

    @page.tool_set_name_header.find('a.sort').click
    no_php_js_errors

    @page.tool_set_name_header[:class].should eq 'highlight'
    @page.tool_set_names.map {|tool_set| tool_set.text}.should == a_to_z_tool_sets.reverse!
  end

  it 'can sort tool sets by status', :stage => 'settings' do
    before_sorting = ['Enabled', 'Enabled', 'Enabled', 'Disabled', 'Enabled', 'Enabled', 'Enabled']
    a_to_z = ['Disabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled']
    z_to_a = ['Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Disabled']

    @page.tool_sets[2].find('input[type="checkbox"]').set true
    @page.tool_sets[4].find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Disable"
    @page.action_submit_button.click
    no_php_js_errors

    # Confirm the right items disabled
    @page.statuses.map {|status| status.text}.should == before_sorting

    # Sort a-z
    @page.status_header.find('a.sort').click
    no_php_js_errors

    @page.statuses.map {|status| status.text}.should == a_to_z

    # Sort z-a
    @page.status_header.find('a.sort').click
    no_php_js_errors

    @page.statuses.map {|status| status.text}.should == z_to_a
  end

  it 'can navigate back to settings from tool set', :stage => 'toolset' do
    @page.breadcrumb.find('li:nth-child(2) a').click
    no_php_js_errors
    @page.displayed?

    confirm_settings_page @page
  end

  it 'can create a new tool set', :stage => 'toolset' do
    @page.tool_set_name.set 'Empty'
    @page.tool_set_save_and_close_button.click

    no_php_js_errors
    @page.displayed?
    confirm_settings_page @page

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include "Tool set created"
    @page.alert.text.should include "Empty has been successfully created."

    @page.should have_css 'tr.selected'
    @page.find('tr.selected').text.should include "Empty"
  end

  it 'can edit a tool set', :stage => 'settings' do
    @page.tool_sets[1].find('li.edit a').click
    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.tool_set_name.set 'Rspec Edited'
    @page.tool_set_save_button.click

    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.tool_set_name.value.should eq "Rspec Edited"

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include "Tool set updated"
  end

  it 'ensures tool set names are unique', :stage => 'toolset' do
    @page.tool_set_name.set 'Default'
    @page.tool_set_save_and_close_button.click

    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.tool_set_name.value.should eq "Default"

    @page.should have_alert
    @page.should have_alert_error
    @page.alert.text.should include "Tool set error"
    @page.alert.text.should include "We were unable to save the tool set, please review and fix errors below."

      should_have_form_errors(@page)

     @page.should have_text 'The tool set name must be unique'
  end

  it 'requires a tool set name', :stage => 'toolset' do
    @page.tool_set_save_and_close_button.click

    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.tool_set_name.value.should eq ""

    @page.should have_alert
    @page.should have_alert_error
    @page.alert.text.should include "Tool set error"
    @page.alert.text.should include "We were unable to save the tool set, please review and fix errors below."

      should_have_form_errors(@page)

     @page.should have_text 'This field is required'
  end

  it 'disallows XSS strings as a tool set name', :stage => 'toolset' do
    @page.tool_set_name.set '<script>Haha'
    @page.tool_set_save_and_close_button.click

    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.tool_set_name.value.should eq "<script>Haha"

    @page.should have_alert
    @page.should have_alert_error
    @page.alert.text.should include "Tool set error"
    @page.alert.text.should include "We were unable to save the tool set, please review and fix errors below."

      should_have_form_errors(@page)

     @page.should have_text 'The tool set name must not include special characters'
  end

  it 'persists tool checkboxes on validation errors', :stage => 'toolset' do
    @page.choose_tools[0].click
    @page.choose_tools[1].click
    @page.choose_tools[2].click

    @page.tool_set_save_and_close_button.click

    no_php_js_errors
    @page.displayed?
    confirm_toolset_page @page

    @page.choose_tools[0].should be_checked
    @page.choose_tools[1].should be_checked
    @page.choose_tools[2].should be_checked
  end

end
