require './bootstrap.rb'

feature 'Add-On Manager' do

	before(:each) do
		cp_session

		@page = AddonManager.new
		@page.load

		@page.displayed?
		@page.title.text.should eq 'Add-On Manager'
		@page.should have_phrase_search

		@page.should have_status_filter
		@page.should have_developer_filter
		@page.should have_perpage_filter

		@page.should have_addons

		@page.should have_bulk_action
		@page.should have_action_submit_button
	end

	it 'shows the Add-On Manger' do
		@page.addon_name_header[:class].should eq 'highlight'
		@page.should have_pagination
		@page.should have(6).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
		@page.should have(21).addons # Default is 20 per page + 1 for header
	end

	it 'can change page size' do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		@page.perpage_filter.text.should eq "show (50)"
		@page.should_not have_pagination
		@page.should have(43).addons #42 'add-ons' + 1 for header
	end

	it 'can reverse sort by Add-On name' do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		a_to_z_addons = @page.addon_names.map {|addon| addon.text}

		@page.addon_name_header.find('a.sort').click
		no_php_js_errors

		@page.addon_name_header[:class].should eq 'highlight'
		@page.addon_names.map {|addon| addon.text}.should == a_to_z_addons.reverse!

		@page.should_not have_pagination
	end

	it 'can sort by Version' do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		versions = @page.versions.map {|version| version.text}

		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.version_header[:class].should eq 'highlight'
		sorted_versions = @page.versions.map {|version| version.text}
		sorted_versions.should_not == versions
		sorted_versions[0].should == '--'

		@page.should_not have_pagination
	end

	it 'can reverse sort by Version' do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		versions = @page.versions.map {|version| version.text}

		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.version_header[:class].should eq 'highlight'
		sorted_versions = @page.versions.map {|version| version.text}
		sorted_versions.should_not == versions
		sorted_versions[-1].should == '--'

		@page.should_not have_pagination
	end

	it 'can filter by status' do
		# By installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		@page.status_filter.text.should eq "status (installed)"
		@page.should_not have_css 'tr.not-installed'
		@page.should_not have_pagination
		@page.should have(18).addons

		# By uninstalled
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "uninstalled"
		no_php_js_errors

		@page.status_filter.text.should eq "status (uninstalled)"
		@page.should have_css 'tr.not-installed'
		@page.all('tr.not-installed').count().should == 20
		@page.should have_pagination
		@page.should have(21).addons

		# By 'needs updates'
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "needs updates"
		no_php_js_errors

		@page.status_filter.text.should eq "status (needs updates)"
		@page.should_not have_css 'tr.not-installed'
		@page.should_not have_pagination
		@page.should have(2).addons # Email + Header
	end

	it 'can filter by developer' do
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		# First by EllisLab
		@page.developer_filter.click
		@page.wait_until_developer_filter_menu_visible
		@page.developer_filter_menu.click_link "EllisLab"
		no_php_js_errors

		@page.developer_filter.text.should eq 'developer (EllisLab)'
		@page.should have(36).addons

		# Now by Third Party
		# @page.developer_filter.click
		# @page.wait_until_developer_filter_menu_visible
		# @page.developer_filter_menu.click_link "Third Party"
		# no_php_js_errors
		#
		# @page.developer_filter.text.should eq 'developer (Third Party)'
		# @page.should have(8).addons
	end

	it 'retains filters on sort' do
		# Filter by Third Party
		@page.developer_filter.click
		@page.wait_until_developer_filter_menu_visible
		@page.developer_filter_menu.click_link "EllisLab"
		no_php_js_errors

		versions = @page.versions.map {|version| version.text}

		# Sort by Version
		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.developer_filter.text.should eq 'developer (EllisLab)'
		@page.version_header[:class].should eq 'highlight'
		sorted_versions = @page.versions.map {|version| version.text}
		sorted_versions.should_not == versions
		sorted_versions[0].should == '--'

		@page.should_not have_pagination
		@page.should have(8).addons
	end

	it 'retains sort on filtering' do
		# Reverse sort by Version
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		versions = @page.versions.map {|version| version.text}

		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.version_header.find('a.sort').click
		no_php_js_errors

		@page.version_header[:class].should eq 'highlight'
		sorted_versions = @page.versions.map {|version| version.text}
		sorted_versions.should_not == versions
		sorted_versions[-1].should == '--'

		# Filter by Status
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		@page.version_header[:class].should eq 'highlight'
		sorted_versions = @page.versions.map {|version| version.text}
		sorted_versions[-1].should == '1.0'
	end

	it 'retains filters on searching' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		addon_name = @page.addon_names[0].text
		@page.phrase_search.set addon_name
		@page.search_submit_button.click
		no_php_js_errors

		# The filter should not change
		@page.status_filter.text.should eq "status (installed)"
		@page.heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
		@page.phrase_search.value.should eq addon_name
		@page.should have_text addon_name
		@page.should have(2).addons
	end

	it 'retains sort on searching' do
		# Sort by Version
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		versions = @page.versions.map {|version| version.text}

		@page.version_header.find('a.sort').click
		no_php_js_errors

		addon_name = @page.addon_names[0].text
		@page.phrase_search.set addon_name
		@page.search_submit_button.click
		no_php_js_errors

		# The filter should not change
		@page.version_header[:class].should eq 'highlight'
		@page.heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
		@page.phrase_search.value.should eq addon_name
		@page.should have_text addon_name
		@page.should have(2).addons
	end

	it 'can combine filters' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		@page.should_not have_css 'tr.not-installed'
		@page.should_not have_pagination

		# Now by EllisLab
		@page.developer_filter.click
		@page.wait_until_developer_filter_menu_visible
		@page.developer_filter_menu.click_link "EllisLab"
		no_php_js_errors

		@page.should have_no_results
		@page.should_not have_pagination
		@page.should_not have_bulk_action
	end

	it 'shows the Prev button when on page 2' do
		click_link "Next"
		no_php_js_errors

		@page.should have_pagination
		@page.should have(7).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
	end

	it 'can search by phrases' do
		@page.phrase_search.set 'RSS'
		@page.search_submit_button.click
		no_php_js_errors

		@page.heading.text.should eq 'Search Results we found 3 results for "RSS"'
		@page.phrase_search.value.should eq 'RSS'
		@page.should have_text 'RSS'
		@page.should have(4).addons
	end

	it 'shows no results on a failed search' do
		@page.phrase_search.set 'NoSuchAddOn'
		@page.search_submit_button.click

		@page.heading.text.should eq 'Search Results we found 0 results for "NoSuchAddOn"'
		@page.phrase_search.value.should eq 'NoSuchAddOn'
		@page.should have_no_results
		@page.should_not have_pagination
		@page.should_not have_bulk_action
	end

	it 'can install a single add-on' do
		# First by uninstalled
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "uninstalled"
		no_php_js_errors

		addon_name = @page.addon_names[0].text

		# Header at 0, first "real" row is 1
		@page.addons[1].find('ul.toolbar li.install a.add').click
		no_php_js_errors

		# The filter should not change
		@page.status_filter.text.should eq "status (uninstalled)"
		@page.should have_alert
		@page.alert.text.should include "Add-Ons Installed"
		@page.alert.text.should include addon_name
		@page.addons.should_not have_text addon_name
	end

	it 'retains search results after installing' do
		addon_name = @page.addon_names[0].text

		# Search
		@page.phrase_search.set addon_name
		@page.search_submit_button.click
		no_php_js_errors

		@page.heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
		@page.phrase_search.value.should eq addon_name
		@page.should have_text addon_name

		# Install
		@page.addons[1].find('ul.toolbar li.install a.add').click
		no_php_js_errors

		@page.heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
		@page.phrase_search.value.should eq addon_name
		@page.should have_text addon_name
	end

	it 'can bulk-install add-ons' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "uninstalled"
		no_php_js_errors

		# Show 50 should show everything
		@page.perpage_filter.click
		@page.wait_until_perpage_filter_menu_visible
		@page.perpage_filter_menu.click_link "50 results"
		no_php_js_errors

		addons = @page.addon_names.map {|addon| addon.text}

		# Header at 0, first "real" row is 1
		@page.checkbox_header.find('input[type="checkbox"]').set true
		@page.bulk_action.select "Install"
		@page.action_submit_button.click
		no_php_js_errors

		# The filter should not change
		@page.status_filter.text.should eq "status (uninstalled)"
		@page.should have_alert
		@page.alert.text.should include "Add-Ons Installed"
		@page.alert.text.should include addons.join(', ')
		@page.should have_no_results
		@page.should_not have_pagination
		@page.should_not have_bulk_action
	end

	it 'displays an itemzied modal when attempting to remove 5 or less add-on' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		addon_name = @page.addon_names[0].text

		# Header at 0, first "real" row is 1
		@page.addons[1].find('input[type="checkbox"]').set true
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include addon_name
		@page.modal.all('.checklist li').length.should eq 1
	end

	it 'displays a bulk confirmation modal when attempting to remove more than 5 add-ons' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		@page.checkbox_header.find('input[type="checkbox"]').set true
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include 'Add-On: 17 Add-Ons'
	end

	it 'can remove add-ons' do
		# First by installed
		@page.status_filter.click
		@page.wait_until_status_filter_menu_visible
		@page.status_filter_menu.click_link "installed"
		no_php_js_errors

		addons = @page.addon_names.map {|addon| addon.text}
		@page.checkbox_header.find('input[type="checkbox"]').set true
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		# The filter should not change
		@page.status_filter.text.should eq "status (installed)"
		@page.should have_alert
		@page.alert.text.should include "Add-Ons Removed"
		@page.alert.text.should include addons.join(', ')
	end

	# The settings buttons "work" (200 response)
	it 'can navigate to a settings page' do
		@page.phrase_search.set 'Rich Text Editor'
		@page.search_submit_button.click
		no_php_js_errors

		@page.find('ul.toolbar li.settings a').click
		no_php_js_errors
	end

	# The guide buttons "work" (200 response)
	it 'can navigate to a manual page' do
		@page.phrase_search.set 'Rich Text Editor'
		@page.search_submit_button.click
		no_php_js_errors

		@page.find('ul.toolbar li.manual a').click
		no_php_js_errors
	end

	# @TODO - Test updating a single add-on
	# @TODO - Test bulk updating add-ons

end
