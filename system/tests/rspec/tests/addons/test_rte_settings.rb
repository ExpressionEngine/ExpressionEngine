require './bootstrap.rb'

feature 'RTE Settings' do

	before(:each) do
		cp_session

		# Load some extra tool sets for testing purposes
	    $db.query(IO.read('support/rte-settings/tool_sets.sql'))
	    clear_db_result

		@page = RTESettings.new
		@page.load

		@page.displayed?
		@page.headings[0].text.should eq 'Add-On Manager'
	end

	before(:each, :stage => 'settings') do
		@page.breadcrumb.text.should include 'Add-On Manager'
		@page.breadcrumb.text.should include 'Rich Text Editor Configuration'

		@page.headings[1].text.should eq 'Rich Text Editor Configuration'
		@page.headings[2].text.should eq 'Available Tool Sets'

		@page.should have_enable_switch
		@page.should have_disable_switch
		@page.should have_default_tool_set
		@page.should have_save_settings_button
		@page.should have_create_new_button
		@page.should have_tool_sets
		@page.should have_bulk_action
		@page.should have_action_submit_button

		@page.should_not have_tool_set_name
		@page.should_not have_choose_tools
	end

	before(:each, :stage => 'toolset') do
		@page.breadcrumb.text.should include 'Rich Text Editor Configuration'
		@page.breadcrumb.text.should include 'RTE Tool Set'

		@page.headings[1].text.should include 'RTE Tool Set'

		@page.should_not have_enable_switch
		@page.should_not have_disable_switch
		@page.should_not have_default_tool_set
		@page.should_not have_create_new_button
		@page.should_not have_tool_sets
		@page.should_not have_bulk_action
		@page.should_not have_action_submit_button

		@page.should have_tool_set_name
		@page.should have_choose_tools
		@page.should have_tool_set_submit_button
	end

	it 'shows the RTE Settings page', :stage => 'settings' do
		@page.enable_switch.should be_checked
		@page.disable_switch.should_not be_checked
		@page.default_tool_set.value.should eq '1'
	end

	it 'can search for an add-on', :stage => 'settings' do
		@page.phrase_search.set 'Rich Text Editor'
		@page.search_submit_button.click
		no_php_js_errors

		addon_manager = AddonManager.new
		addon_manager.displayed?
	end

	it 'can navigate back to the add-on manager via the breadcrumb', :stage => 'settings' do
		@page.breadcrumb.find('a').click
		no_php_js_errors

		addon_manager = AddonManager.new
		addon_manager.displayed?
	end

	it 'can disable & enable the rich text editor', :stage => 'settings' do
		@page.disable_switch.click
		@page.save_settings_button.click
		no_php_js_errors

		@page.enable_switch.should_not be_checked
		@page.disable_switch.should be_checked

		@page.enable_switch.click
		@page.save_settings_button.click
		no_php_js_errors

		@page.enable_switch.should be_checked
		@page.disable_switch.should_not be_checked
	end

	it 'only accpets "y" or "n" for enabled setting', :stage => 'settings' do
		@page.enable_switch.set '1'
		@page.save_settings_button.click
		no_php_js_errors

		@page.should have_alert
		@page.alert[:class].should include "success"

		@page.enable_switch.set 'yes'
		@page.save_settings_button.click
		no_php_js_errors

		@page.should have_alert
		@page.alert[:class].should include "success"
	end

	it 'can change the default tool set', :stage => 'settings' do
		@page.default_tool_set.select "Advanced"
		@page.save_settings_button.click
		no_php_js_errors

		@page.default_tool_set.value.should eq "3"
	end

	it 'cannot set a default tool set to an non-existant tool set', :stage => 'settings' do
		@page.selected_default_tool_set.set 101

		@page.save_settings_button.click
		no_php_js_errors

		@page.should have_alert
		@page.alert[:class].should include "issue"
		@page.alert[:class].should_not include "success"
	end

	it 'can disable & enable a single tool set', :stage => 'settings' do
		@page.tool_sets[1].text.should include 'Enabled'

		@page.tool_sets[1].find('input[type="checkbox"]').set true
		@page.bulk_action.select "Disable"
		@page.action_submit_button.click
		no_php_js_errors

		@page.tool_sets[1].text.should include 'Disabled'
		@page.tool_sets[1].text.should_not include 'Enabled'

		@page.tool_sets[1].find('input[type="checkbox"]').set true
		@page.bulk_action.select "Enable"
		@page.action_submit_button.click
		no_php_js_errors

		@page.tool_sets[1].text.should include 'Enabled'
		@page.tool_sets[1].text.should_not include 'Disabled'
	end

	it 'can disable & enable multiple tool set', :stage => 'settings' do
		@page.text.should include 'Enabled'

		@page.checkbox_header.find('input[type="checkbox"]').set true
		@page.bulk_action.select "Disable"
		@page.action_submit_button.click
		no_php_js_errors

		@page.text.should include 'Disabled'
		@page.text.should_not include 'Enabled'

		@page.checkbox_header.find('input[type="checkbox"]').set true
		@page.bulk_action.select "Enable"
		@page.action_submit_button.click
		no_php_js_errors

		@page.text.should include 'Enabled'
		@page.text.should_not include 'Disabled'
	end

	it 'displays an itemzied modal when trying to remove 5 or less tool sets', :stage => 'settings' do
		tool_set_name = @page.tool_set_names[0].text

		# Header at 0, first "real" row is 1
		@page.tool_sets[1].find('input[type="checkbox"]').set true
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
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include "Tool Set: 6 Tool Sets"
	end

	# it 'cannot remove the default tool set', :stage => 'settings' do
	# 	tool_set_name = @page.tool_set_names[1].text
	#
	# 	# Try to remove the tool set "Default"
	# 	@page.tool_sets[2].find('input[type="checkbox"]').set true
	# 	@page.bulk_action.select "Remove"
	# 	@page.action_submit_button.click
	# 	@page.wait_until_modal_visible
	# 	@page.modal_submit_button.click # Submits a form
	# 	no_php_js_errors
	#
	# 	@page.should have_alert
	# 	@page.alert[:class].should include "issue"
	# 	@page.alert.text.should include "The default tool set cannot be removed."
	# 	@page.tool_set_names[1].text.should eq tool_set_name
	# end

	it 'can remove a tool set', :stage => 'settings' do
		@page.tool_sets[1].find('input[type="checkbox"]').set true
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.should have_alert
		@page.alert[:class].should include "success"
		@page.alert.text.should include "Tool sets removed"
		@page.alert.text.should include "1 tool sets were removed."
	end

	it 'can bulk remove tool sets', :stage => 'settings' do
		@page.checkbox_header.find('input[type="checkbox"]').set true

		# Uncheck the Default tool set
		@page.tool_sets[2].find('input[type="checkbox"]').set false
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.should have_alert
		@page.alert[:class].should include "success"
		@page.alert.text.should include "Tool sets removed"
		@page.alert.text.should include "5 tool sets were removed."
	end

	it 'can reverse sort tool sets by name', :stage => 'settings' do
		a_to_z_tool_sets = @page.tool_set_names.map {|tool_set| tool_set.text}

		@page.tool_set_name_header.find('a.sort').click
		no_php_js_errors

		@page.tool_set_name_header[:class].should eq 'highlight'
		@page.tool_set_names.map {|tool_set| tool_set.text}.should == a_to_z_tool_sets.reverse!
	end

	it 'can sort tool sets by status', :stage => 'settings' do
		before_sorting = ['Enabled', 'Disabled', 'Enabled', 'Disabled', 'Enabled', 'Enabled']
		a_to_z = ['Disabled', 'Disabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled']
		z_to_a = ['Enabled', 'Enabled', 'Enabled', 'Enabled', 'Disabled', 'Disabled']

		@page.tool_sets[2].find('input[type="checkbox"]').set true
		@page.tool_sets[4].find('input[type="checkbox"]').set true
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

	# it 'can navigate back to settings from tool set', :stage => 'toolset'
	# it 'can create a new tool set', :stage => 'toolset'
	# it 'can edit a tool set', :stage => 'toolset'
	# it 'ensures tool set names are unique', :stage => 'toolset'
	# it 'requires a tool set name', :stage => 'toolset'
	# it 'disallows XSS strings as a tool set name', :stage => 'toolset'
	# it 'persists tool checkboxes on validation erorrs', :stage => 'toolset'

end