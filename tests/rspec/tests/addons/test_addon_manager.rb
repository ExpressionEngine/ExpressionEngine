require './bootstrap.rb'

feature 'Add-On Manager' do

	before(:each) do
		cp_session

		@page = AddonManager.new
		@page.load

		@page.displayed?
		@page.title.text.should eq 'Add-On Manager'
		@page.should have_phrase_search

		@page.should have_first_party_section
		@page.first_party_heading.text.should eq 'Add-Ons'

		@page.should have_first_party_status_filter
		@page.should have_first_party_perpage_filter

		@page.should have_first_party_addons

		@page.should have_first_party_bulk_action
		@page.should have_first_party_action_submit_button
	end

	describe "First-Party Table Only" do

		before(:each) do
			@page.should_not have_third_party_section
		end

		it 'shows the Add-On Manager' do
			@page.first_party_addon_name_header[:class].should eq 'highlight'
			@page.should have_first_party_pagination
			@page.should have(5).first_party_pages
			@page.first_party_pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
			@page.should have(25).first_party_addons # Default is 25 per page
		end

		it 'can change page size' do
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			@page.first_party_perpage_filter.text.should eq "show (100)"
			@page.should_not have_first_party_pagination
			@page.should have(40).first_party_addons
		end

		it 'can reverse sort by Add-On name' do
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			a_to_z_addons = @page.first_party_addon_names.map {|addon| addon.text}

			@page.first_party_addon_name_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_addon_name_header[:class].should eq 'highlight'
			@page.first_party_addon_names.map {|addon| addon.text}.should == a_to_z_addons.reverse!

			@page.should_not have_first_party_pagination
		end

		it 'can sort by Version' do
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			versions = @page.first_party_versions.map {|version| version.text}

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_version_header[:class].should eq 'highlight'
			sorted_versions = @page.first_party_versions.map {|version| version.text}
			sorted_versions.should_not == versions
			sorted_versions[0].should == '--'

			@page.should_not have_first_party_pagination
		end

		it 'can reverse sort by Version' do
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			versions = @page.first_party_versions.map {|version| version.text}

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_version_header[:class].should eq 'highlight'
			sorted_versions = @page.first_party_versions.map {|version| version.text}
			sorted_versions.should_not == versions
			sorted_versions[-1].should == '--'

			@page.should_not have_first_party_pagination
		end

		it 'can filter by status' do
			# By installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (installed)"
			@page.should_not have_css 'tr.not-installed'
			@page.should_not have_first_party_pagination
			@page.should have(17).first_party_addons

			# By uninstalled
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "uninstalled"
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (uninstalled)"
			@page.should have_css 'tr.not-installed'
			@page.all('tr.not-installed').count().should == 23
			@page.should have(23).first_party_addons

			# By 'needs updates'
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "needs updates"
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (needs updates)"
			@page.should_not have_css 'tr.not-installed'
			@page.should_not have_first_party_pagination

			# RTE has the correct version number now
			@page.should have(2).first_party_addons # Email + RTE
		end

		it 'retains filters on sort' do
			# Filter on status
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (installed)"
			@page.should_not have_css 'tr.not-installed'
			@page.should_not have_first_party_pagination
			@page.should have(17).first_party_addons

			versions = @page.first_party_versions.map {|version| version.text}

			# Sort by Version
			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (installed)"
			@page.first_party_version_header[:class].should eq 'highlight'
			sorted_versions = @page.first_party_versions.map {|version| version.text}
			sorted_versions.should_not == versions
		end

		it 'retains sort on filtering' do
			# Reverse sort by Version
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			versions = @page.first_party_versions.map {|version| version.text}

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			@page.first_party_version_header[:class].should eq 'highlight'
			sorted_versions = @page.first_party_versions.map {|version| version.text}
			sorted_versions.should_not == versions
			sorted_versions[-1].should == '--'

			# Filter by Status
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			@page.first_party_version_header[:class].should eq 'highlight'
			sorted_versions = @page.first_party_versions.map {|version| version.text}
			sorted_versions[-1].should == '1.0'
		end

		it 'retains filters on searching' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			addon_name = @page.first_party_addon_names[0].text
			@page.phrase_search.set addon_name
			@page.search_submit_button.click
			no_php_js_errors

			# The filter should not change
			@page.first_party_status_filter.text.should eq "status (installed)"
			@page.first_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
			@page.phrase_search.value.should eq addon_name
			@page.should have_text addon_name
			@page.should have(1).first_party_addons
		end

		it 'retains sort on searching' do
			# Sort by Version
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			versions = @page.first_party_versions.map {|version| version.text}

			@page.first_party_version_header.find('a.sort').click
			no_php_js_errors

			addon_name = @page.first_party_addon_names[0].text
			@page.phrase_search.set addon_name
			@page.search_submit_button.click
			no_php_js_errors

			# The filter should not change
			@page.first_party_version_header[:class].should eq 'highlight'
			@page.first_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
			@page.phrase_search.value.should eq addon_name
			@page.should have_text addon_name
			@page.should have(1).first_party_addons
		end

		it 'can combine filters' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "uninstalled"
			no_php_js_errors

			@page.should have_css 'tr.not-installed'

			# Now by perpage
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "50 results"
			no_php_js_errors

			@page.first_party_status_filter.text.should eq "status (uninstalled)"
			@page.first_party_perpage_filter.text.should eq 'show (50)'
		end

		it 'shows the Prev button when on page 2' do
			click_link "Next"
			no_php_js_errors

			@page.should have_first_party_pagination
			@page.should have(5).first_party_pages
			@page.first_party_pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
		end

		it 'can search by phrases' do
			@page.phrase_search.set 'RSS'
			@page.search_submit_button.click
			no_php_js_errors

			@page.first_party_heading.text.should eq 'Search Results we found 2 results for "RSS"'
			@page.phrase_search.value.should eq 'RSS'
			@page.should have_text 'RSS'
			@page.should have(2).first_party_addons
		end

		it 'shows no results on a failed search' do
			@page.phrase_search.set 'NoSuchAddOn'
			@page.search_submit_button.click

			@page.first_party_heading.text.should eq 'Search Results we found 0 results for "NoSuchAddOn"'
			@page.phrase_search.value.should eq 'NoSuchAddOn'
			@page.should have_first_party_no_results
			@page.should_not have_first_party_pagination
			@page.should_not have_first_party_bulk_action
		end

		it 'can install a single add-on' do
			# First by uninstalled
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "uninstalled"
			no_php_js_errors

			addon_name = @page.first_party_addon_names[0].text

			# Header at 0, first "real" row is 1
			@page.first_party_addons[0].find('ul.toolbar li.install a.add').click
			no_php_js_errors

			# The filter should not change
			@page.first_party_status_filter.text.should eq "status (uninstalled)"
			@page.should have_first_party_alert
			@page.first_party_alert.text.should include "Add-Ons Installed"
			@page.first_party_alert.text.should include addon_name
			@page.first_party_addons.should_not have_text addon_name
		end

		it 'retains search results after installing' do
			addon_name = @page.first_party_addon_names[0].text

			# Search
			@page.phrase_search.set addon_name
			@page.search_submit_button.click
			no_php_js_errors

			@page.first_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
			@page.phrase_search.value.should eq addon_name
			@page.should have_text addon_name

			# Install
			@page.first_party_addons[0].find('ul.toolbar li.install a.add').click
			no_php_js_errors

			@page.first_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
			@page.phrase_search.value.should eq addon_name
			@page.should have_text addon_name
		end

		it 'can bulk-install add-ons' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "uninstalled"
			no_php_js_errors

			# Show 100 should show everything
			@page.first_party_perpage_filter.click
			@page.wait_until_first_party_perpage_filter_menu_visible
			@page.first_party_perpage_filter_menu.click_link "100 results"
			no_php_js_errors

			addons = @page.first_party_addon_names.map {|addon| addon.text}

			# Header at 0, first "real" row is 1
			@page.first_party_checkbox_header.find('input[type="checkbox"]').set true
			@page.first_party_bulk_action.select "Install"
			@page.first_party_action_submit_button.click
			no_php_js_errors

			# The filter should not change
			@page.first_party_status_filter.text.should eq "status (uninstalled)"
			@page.should have_first_party_alert
			@page.first_party_alert.text.should include "Add-Ons Installed"
			@page.first_party_alert.text.should include addons.join(' ')
			@page.should have_first_party_no_results
			@page.should_not have_first_party_pagination
			@page.should_not have_first_party_bulk_action
		end

		it 'displays an itemzied modal when attempting to remove 5 or less add-on' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			addon_name = @page.first_party_addon_names[0].text

			# Header at 0, first "real" row is 1
			@page.first_party_addons[0].find('input[type="checkbox"]').set true
			@page.first_party_bulk_action.select "Remove"
			@page.first_party_action_submit_button.click

			@page.wait_until_modal_visible
			@page.modal_title.text.should eq "Confirm Removal"
			@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
			@page.modal.text.should include addon_name
			@page.modal.all('.checklist li').length.should eq 1
		end

		it 'displays a bulk confirmation modal when attempting to remove more than 5 add-ons' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			@page.first_party_checkbox_header.find('input[type="checkbox"]').set true
			@page.first_party_bulk_action.select "Remove"
			@page.first_party_action_submit_button.click

			@page.wait_until_modal_visible
			@page.modal_title.text.should eq "Confirm Removal"
			@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
			@page.modal.text.should include 'Add-On: 17 Add-Ons'
		end

		it 'can remove add-ons' do
			# First by installed
			@page.first_party_status_filter.click
			@page.wait_until_first_party_status_filter_menu_visible
			@page.first_party_status_filter_menu.click_link "installed"
			no_php_js_errors

			addons = @page.first_party_addon_names.map {|addon| addon.text}
			@page.first_party_checkbox_header.find('input[type="checkbox"]').set true
			@page.first_party_bulk_action.select "Remove"
			@page.first_party_action_submit_button.click
			@page.wait_until_modal_visible
			@page.modal_submit_button.click # Submits a form
			no_php_js_errors

			# The filter should not change
			@page.first_party_status_filter.text.should eq "status (installed)"
			@page.should have_first_party_alert
			@page.first_party_alert.text.should include "Add-Ons Removed"
			@page.first_party_alert.text.should include addons.join(' ')
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

	describe "Third-Party Table" do

		before(:all) do
			@addon_dir = File.expand_path('../../system/user/addons/')
			FileUtils.cp_r Dir.glob('support/add-on-manager/test_*'), @addon_dir
		end

		after(:all) do
			FileUtils.rm_rf Dir.glob('../../system/user/addons/test_*')
		end

		before(:each) do
			@page.should have_third_party_section
			@page.third_party_heading.text.should eq 'Third Party Add-Ons'

			@page.should have_third_party_status_filter
			@page.should have_third_party_developer_filter
			@page.should have_third_party_perpage_filter

			@page.should have_third_party_addons

			@page.should have_third_party_bulk_action
			@page.should have_third_party_action_submit_button
		end

		before(:each, :install => true) do
			@page.third_party_checkbox_header.find('input[type="checkbox"]').set true
			@page.third_party_bulk_action.select "Install"
			@page.third_party_action_submit_button.click
			no_php_js_errors
		end

		describe "Just this Table" do

			it 'shows the third party add-ons' do
				@page.third_party_addon_name_header[:class].should eq 'highlight'
				@page.should_not have_third_party_pagination
				@page.should have(6).third_party_addons
			end

			it 'can change page size' do
				@page.third_party_perpage_filter.click
				@page.wait_until_third_party_perpage_filter_menu_visible
				@page.third_party_perpage_manual_filter.set 5
				@page.third_party_action_submit_button.click
				no_php_js_errors

				@page.third_party_perpage_filter.text.should eq "show (5)"
				@page.should have_third_party_pagination
				@page.should have(5).third_party_addons
			end

			it 'can reverse sort by Add-On name' do
				a_to_z_addons = @page.third_party_addon_names.map {|addon| addon.text}

				@page.third_party_addon_name_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_addon_name_header[:class].should eq 'highlight'
				@page.third_party_addon_names.map {|addon| addon.text}.should == a_to_z_addons.reverse!
			end

			it 'can sort by Version', :install => true do
				versions = @page.third_party_versions.map {|version| version.text}

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_version_header[:class].should eq 'highlight'
				sorted_versions = @page.third_party_versions.map {|version| version.text}
				sorted_versions.should_not == versions
				sorted_versions[0].should == '1.1'
				sorted_versions[-1].should == '1.6'
			end

			it 'can reverse sort by Version', :install => true do
				versions = @page.third_party_versions.map {|version| version.text}

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_version_header[:class].should eq 'highlight'
				sorted_versions = @page.third_party_versions.map {|version| version.text}
				sorted_versions.should_not == versions
				sorted_versions[0].should == '1.6'
				sorted_versions[-1].should == '1.1'

				@page.should_not have_third_party_pagination
			end

			it 'can filter by status' do
				# By installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "installed"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (installed)"
				@page.should_not have_third_party_pagination
				@page.should have_third_party_no_results

				# By uninstalled
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.should have_css 'tr.not-installed'
				@page.third_party_addons('tr.not-installed').count().should == 6
				@page.should_not have_third_party_pagination
				@page.should have(6).third_party_addons

				# By 'needs updates'
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "needs updates"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (needs updates)"
				@page.third_party_addons.should_not have_css 'tr.not-installed'
				@page.should_not have_third_party_pagination
				@page.should have_third_party_no_results
			end

			it 'can filter by developer' do
				# First by Test LLC
				@page.third_party_developer_filter.click
				@page.wait_until_third_party_developer_filter_menu_visible
				@page.third_party_developer_filter_menu.click_link "Test LLC"
				no_php_js_errors

				@page.third_party_developer_filter.text.should eq 'developer (Test LLC)'
				@page.should have(2).third_party_addons

				# Now by Example Inc.
				@page.third_party_developer_filter.click
				@page.wait_until_third_party_developer_filter_menu_visible
				@page.third_party_developer_filter_menu.click_link "Example Inc."
				no_php_js_errors

				@page.third_party_developer_filter.text.should eq 'developer (Example Inc.)'
				@page.should have(4).third_party_addons
			end

			it 'retains filters on sort' do
				# Filter on status
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.should have_css 'tr.not-installed'
				@page.should_not have_third_party_pagination
				@page.should have(6).third_party_addons

				# Sort by Version
				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.third_party_version_header[:class].should eq 'highlight'
			end

			it 'retains sort on filtering' do
				# Reverse sort by Version
				versions = @page.third_party_versions.map {|version| version.text}

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.third_party_version_header[:class].should eq 'highlight'

				# Filter by Status
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.third_party_version_header[:class].should eq 'highlight'
			end

			it 'retains filters on searching' do
				# First by installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				addon_name = @page.third_party_addon_names[0].text
				@page.phrase_search.set addon_name
				@page.search_submit_button.click
				no_php_js_errors

				# The filter should not change
				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.third_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
				@page.phrase_search.value.should eq addon_name
				@page.should have_text addon_name
				@page.should have(1).third_party_addons
			end

			it 'retains sort on searching' do
				versions = @page.third_party_versions.map {|version| version.text}

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				addon_name = @page.third_party_addon_names[0].text
				@page.phrase_search.set addon_name
				@page.search_submit_button.click
				no_php_js_errors

				# The filter should not change
				@page.third_party_version_header[:class].should eq 'highlight'
				@page.third_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
				@page.phrase_search.value.should eq addon_name
				@page.should have_text addon_name
				@page.should have(1).third_party_addons
			end

			it 'can combine filters' do
				# First by installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				@page.should have_css 'tr.not-installed'

				# Now by perpage
				@page.third_party_perpage_filter.click
				@page.wait_until_third_party_perpage_filter_menu_visible
				@page.third_party_perpage_filter_menu.click_link "25 results"
				no_php_js_errors

				@page.third_party_status_filter.text.should eq "status (uninstalled)"
				@page.third_party_perpage_filter.text.should eq 'show (25)'
			end

			it 'shows the Prev button when on page 2' do
				@page.third_party_perpage_filter.click
				@page.wait_until_third_party_perpage_filter_menu_visible
				@page.third_party_perpage_manual_filter.set 5
				@page.third_party_action_submit_button.click
				no_php_js_errors

				@page.third_party_pagination.click_link "Next"
				no_php_js_errors

				@page.should have_third_party_pagination
				@page.should have(5).third_party_pages
				@page.third_party_pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
			end

			it 'can search by phrases' do
				@page.phrase_search.set 'Test F'
				@page.search_submit_button.click
				no_php_js_errors

				@page.third_party_heading.text.should eq 'Search Results we found 2 results for "Test F"'
				@page.phrase_search.value.should eq 'Test F'
				@page.should have_text 'Test F'
				@page.should have(2).third_party_addons
			end

			it 'shows no results on a failed search' do
				@page.phrase_search.set 'NoSuchAddOn'
				@page.search_submit_button.click

				@page.third_party_heading.text.should eq 'Search Results we found 0 results for "NoSuchAddOn"'
				@page.phrase_search.value.should eq 'NoSuchAddOn'
				@page.should have_third_party_no_results
				@page.should_not have_third_party_pagination
				@page.should_not have_third_party_bulk_action
			end

			it 'can install a single add-on' do
				addon_name = @page.third_party_addon_names[0].text

				# Header at 0, first "real" row is 1
				@page.third_party_addons[0].find('ul.toolbar li.install a.add').click
				no_php_js_errors

				# The filter should not change
				@page.should_not have_first_party_alert
				@page.should have_third_party_alert
				@page.third_party_alert.text.should include "Add-Ons Installed"
				@page.third_party_alert.text.should include addon_name
				@page.third_party_addons.should_not have_text addon_name
			end

			it 'retains search results after installing' do
				addon_name = @page.third_party_addon_names[0].text

				# Search
				@page.phrase_search.set addon_name
				@page.search_submit_button.click
				no_php_js_errors

				@page.third_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
				@page.phrase_search.value.should eq addon_name
				@page.should have_text addon_name

				# Install
				@page.third_party_addons[0].find('ul.toolbar li.install a.add').click
				no_php_js_errors

				@page.third_party_heading.text.should eq 'Search Results we found 1 results for "' + addon_name + '"'
				@page.phrase_search.value.should eq addon_name
				@page.should have_text addon_name
			end

			it 'can bulk-install add-ons' do
				addons = @page.third_party_addon_names.map {|addon| addon.text}

				@page.third_party_checkbox_header.find('input[type="checkbox"]').set true
				@page.third_party_bulk_action.select "Install"
				@page.third_party_action_submit_button.click
				no_php_js_errors

				# The filter should not change
				@page.should_not have_first_party_alert
				@page.should have_third_party_alert
				@page.third_party_alert.text.should include "Add-Ons Installed"
				@page.third_party_alert.text.should include addons.join(' ')
			end

			it 'displays an itemzied modal when attempting to remove 5 or less add-on', :install => true do
				# First by installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "installed"
				no_php_js_errors

				addon_name = @page.third_party_addon_names[0].text

				# Header at 0, first "real" row is 1
				@page.third_party_addons[0].find('input[type="checkbox"]').set true
				@page.third_party_bulk_action.select "Remove"
				@page.third_party_action_submit_button.click

				@page.wait_until_modal_visible
				@page.modal_title.text.should eq "Confirm Removal"
				@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
				@page.modal.text.should include addon_name
				@page.modal.all('.checklist li').length.should eq 1
			end

			it 'displays a bulk confirmation modal when attempting to remove more than 5 add-ons', :install => true do
				# First by installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "installed"
				no_php_js_errors

				@page.third_party_checkbox_header.find('input[type="checkbox"]').set true
				@page.third_party_bulk_action.select "Remove"
				@page.third_party_action_submit_button.click

				@page.wait_until_modal_visible
				@page.modal_title.text.should eq "Confirm Removal"
				@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
				@page.modal.text.should include 'Add-On: 6 Add-Ons'
			end

			it 'can remove add-ons', :install => true do
				# First by installed
				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "installed"
				no_php_js_errors

				addons = @page.third_party_addon_names.map {|addon| addon.text}
				@page.third_party_checkbox_header.find('input[type="checkbox"]').set true
				@page.third_party_bulk_action.select "Remove"
				@page.third_party_action_submit_button.click
				@page.wait_until_modal_visible
				@page.modal_submit_button.click # Submits a form
				no_php_js_errors

				# The filter should not change
				@page.third_party_status_filter.text.should eq "status (installed)"
				@page.should_not have_first_party_alert
				@page.should have_third_party_alert
				@page.third_party_alert.text.should include "Add-Ons Removed"
				@page.third_party_alert.text.should include addons.join(' ')
			end

			# The settings buttons "work" (200 response)
			# it 'can navigate to a settings page' do
			# 	@page.phrase_search.set 'Rich Text Editor'
			# 	@page.search_submit_button.click
			# 	no_php_js_errors
			#
			# 	@page.find('ul.toolbar li.settings a').click
			# 	no_php_js_errors
			# end

			# The guide buttons "work" (200 response)
			it 'can navigate to a manual page', :install => true do
				@page.third_party_addons[0].find('ul.toolbar li.manual a').click
				no_php_js_errors
			end

			# @TODO - Test updating a single add-on
			# @TODO - Test bulk updating add-ons
		end

		describe "Acting on Both Tables" do

			before(:each) do
				@page.third_party_perpage_filter.click
				@page.wait_until_third_party_perpage_filter_menu_visible
				@page.third_party_perpage_manual_filter.set 5
				@page.third_party_action_submit_button.click
				no_php_js_errors
			end

			it "changes pages independently" do
				@page.first_party_pages[1].text.should eq '1'
				@page.first_party_pages[1][:class].should eq 'act'
				@page.third_party_pages[1].text.should eq '1'
				@page.third_party_pages[1][:class].should eq 'act'

				@page.first_party_pagination.click_link "Last"
				no_php_js_errors

				@page.first_party_pages[3].text.should eq '2'
				@page.first_party_pages[3][:class].should eq 'act'
				@page.third_party_pages[1].text.should eq '1'
				@page.third_party_pages[1][:class].should eq 'act'

				@page.third_party_pagination.click_link "Next"
				no_php_js_errors

				@page.first_party_pages[3].text.should eq '2'
				@page.first_party_pages[3][:class].should eq 'act'
				@page.third_party_pages[3].text.should eq '2'
				@page.third_party_pages[3][:class].should eq 'act'
			end

			it "filters independently" do
				@page.first_party_status_filter.text.should eq "status"
				@page.third_party_status_filter.text.should eq "status"

				@page.first_party_status_filter.click
				@page.wait_until_first_party_status_filter_menu_visible
				@page.first_party_status_filter_menu.click_link "installed"
				no_php_js_errors

				@page.first_party_status_filter.text.should eq "status (installed)"
				@page.third_party_status_filter.text.should eq "status"

				@page.third_party_status_filter.click
				@page.wait_until_third_party_status_filter_menu_visible
				@page.third_party_status_filter_menu.click_link "uninstalled"
				no_php_js_errors

				@page.first_party_status_filter.text.should eq "status (installed)"
				@page.third_party_status_filter.text.should eq "status (uninstalled)"
			end

			it "sorts independently" do
				@page.first_party_addon_name_header[:class].should eq 'highlight'
				@page.third_party_addon_name_header[:class].should eq 'highlight'

				@page.first_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.first_party_version_header[:class].should eq 'highlight'
				@page.third_party_addon_name_header[:class].should eq 'highlight'

				@page.third_party_version_header.find('a.sort').click
				no_php_js_errors

				@page.first_party_version_header[:class].should eq 'highlight'
				@page.third_party_version_header[:class].should eq 'highlight'
			end

			it "searches both tables" do
				@page.phrase_search.set 'Test'
				@page.search_submit_button.click
				no_php_js_errors

				@page.first_party_heading.text.should eq 'Search Results we found 1 results for "Test"'
				@page.third_party_heading.text.should eq 'Search Results we found 6 results for "Test"'
				@page.phrase_search.value.should eq 'Test'
				@page.should have_text 'Test'
			end

			describe "keeps sort when paging the other table" do
				it "can sort First Party & page Third Party" do
					@page.first_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'

					@page.third_party_pagination.click_link "Next"
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'
					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'
				end

				it "can sort Third Party & page First Party" do
					@page.third_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.third_party_version_header[:class].should eq 'highlight'

					@page.first_party_pagination.click_link "Last"
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'
					@page.third_party_version_header[:class].should eq 'highlight'
				end
			end

			describe "keeps sort when filtering the other table" do
				it "can sort First Party & page Third Party" do
					@page.first_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'

					@page.third_party_status_filter.click
					@page.wait_until_third_party_status_filter_menu_visible
					@page.third_party_status_filter_menu.click_link "uninstalled"
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'
					@page.third_party_status_filter.text.should eq "status (uninstalled)"
				end

				it "can sort Third Party & page First Party" do
					@page.third_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.third_party_version_header[:class].should eq 'highlight'

					@page.first_party_status_filter.click
					@page.wait_until_first_party_status_filter_menu_visible
					@page.first_party_status_filter_menu.click_link "installed"
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"
					@page.third_party_version_header[:class].should eq 'highlight'
				end
			end

			describe "keeps paging when sorting the other table" do
				it "can page First Party & sort Third Party" do
					@page.first_party_pagination.click_link "Last"
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'

					@page.third_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'
					@page.third_party_version_header[:class].should eq 'highlight'
				end

				it "can page Third Paty & sort First Party" do
					@page.third_party_pagination.click_link "Next"
					no_php_js_errors

					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'

					@page.first_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'
					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'
				end
			end

			describe "keeps paging when filtering the other table" do
				it "can page First Party & filter Third Party" do
					@page.first_party_pagination.click_link "Last"
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'

					@page.third_party_status_filter.click
					@page.wait_until_third_party_status_filter_menu_visible
					@page.third_party_status_filter_menu.click_link "uninstalled"
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'
					@page.third_party_status_filter.text.should eq "status (uninstalled)"
				end

				it "can page Third Paty & filter First Party" do
					@page.third_party_pagination.click_link "Next"
					no_php_js_errors

					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'

					@page.first_party_status_filter.click
					@page.wait_until_first_party_status_filter_menu_visible
					@page.first_party_status_filter_menu.click_link "installed"
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"
					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'
				end
			end

			describe "keeps the filter when paging the other table" do
				it "can filter First Party & page Third Party" do
					@page.first_party_status_filter.click
					@page.wait_until_first_party_status_filter_menu_visible
					@page.first_party_status_filter_menu.click_link "installed"
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"

					@page.third_party_pagination.click_link "Next"
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"
					@page.third_party_pages[3].text.should eq '2'
					@page.third_party_pages[3][:class].should eq 'act'
				end

				it "can filter Third Party & page First Party" do
					@page.third_party_status_filter.click
					@page.wait_until_third_party_status_filter_menu_visible
					@page.third_party_status_filter_menu.click_link "uninstalled"
					no_php_js_errors

					@page.third_party_status_filter.text.should eq "status (uninstalled)"

					@page.first_party_pagination.click_link "Last"
					no_php_js_errors

					@page.first_party_pages[3].text.should eq '2'
					@page.first_party_pages[3][:class].should eq 'act'
					@page.third_party_status_filter.text.should eq "status (uninstalled)"
				end
			end

			describe "keeps the filter when sorting the other table" do
				it "can filter First Party & page Third Party" do
					@page.first_party_status_filter.click
					@page.wait_until_first_party_status_filter_menu_visible
					@page.first_party_status_filter_menu.click_link "installed"
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"

					@page.third_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_status_filter.text.should eq "status (installed)"
					@page.third_party_version_header[:class].should eq 'highlight'
				end

				it "can filter Third Party & page First Party" do
					@page.third_party_status_filter.click
					@page.wait_until_third_party_status_filter_menu_visible
					@page.third_party_status_filter_menu.click_link "uninstalled"
					no_php_js_errors

					@page.third_party_status_filter.text.should eq "status (uninstalled)"

					@page.first_party_version_header.find('a.sort').click
					no_php_js_errors

					@page.first_party_version_header[:class].should eq 'highlight'
					@page.third_party_status_filter.text.should eq "status (uninstalled)"
				end
			end

		end
	end

end
