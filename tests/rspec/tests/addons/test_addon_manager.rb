require './bootstrap.rb'

feature 'Add-On Manager' do

  before(:each) do
    cp_session

    @page = AddonManager.new
    @page.load

    @page.displayed?
    @page.title.text.should eq 'Add-On Manager'

    @page.should have_first_party_section
    @page.first_party_heading.text.should eq 'Add-Ons'

    @page.should have_first_party_status_filter

    @page.should have_first_party_addons
  end

  describe "First-Party Table Only" do

    before(:each) do
      @page.should_not have_third_party_section
    end

    it 'shows the Add-On Manager' do
      @page.first_party_addon_name_header[:class].should eq 'highlight'
    end

    it 'can reverse sort by Add-On name' do
      a_to_z_addons = @page.first_party_addon_names.map {|addon| addon.text}

      @page.first_party_addon_name_header.find('a.sort').click
      no_php_js_errors

      @page.first_party_addon_name_header[:class].should eq 'highlight'
      @page.first_party_addon_names.map {|addon| addon.text}.should == a_to_z_addons.reverse!

      @page.should_not have_first_party_pagination
    end

    it 'can sort by Version' do
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
      @page.should have(5).first_party_addons

      # By uninstalled
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "uninstalled"
      no_php_js_errors

      @page.first_party_status_filter.text.should eq "status (uninstalled)"
      @page.should have_css 'tr.not-installed'
      @page.all('tr.not-installed').count().should == 13
      @page.should have(13).first_party_addons

      # By 'needs updates'
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "needs updates"
      no_php_js_errors

      @page.first_party_status_filter.text.should eq "status (needs updates)"
      @page.should_not have_css 'tr.not-installed'
      @page.should_not have_first_party_pagination

      # RTE has the correct version number now
      @page.should have(2).first_party_addons
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
      @page.should have(5).first_party_addons

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
      sorted_versions[-1].should == '1.0.0'
    end

    it 'can install a single add-on' do
      # First by uninstalled
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "uninstalled"
      no_php_js_errors

      addon_name = @page.first_party_addon_names[0].text

      # Header at 0, first "real" row is 1
      @page.first_party_addons[0].find('ul.toolbar li.txt-only a.add').click
      no_php_js_errors

      # The filter should not change
      @page.first_party_status_filter.text.should eq "status (uninstalled)"
      @page.should have_alert
      @page.alert.text.should include "Add-Ons Installed"
      @page.alert.text.should include addon_name
      @page.first_party_addons.should_not have_text addon_name
    end

    it 'can bulk-install add-ons' do
      # First by installed
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "uninstalled"
      no_php_js_errors

      addons = @page.first_party_addon_names.map {|addon| addon.text}

      # Header at 0, first "real" row is 1
      @page.first_party_checkbox_header.find('input[type="checkbox"]').set true
      @page.wait_until_first_party_bulk_action_visible
      @page.first_party_bulk_action.select "Install"
      @page.first_party_action_submit_button.click
      no_php_js_errors

      # The filter should not change
      @page.first_party_status_filter.text.should eq "status (uninstalled)"
      @page.should have_alert
      @page.alert.text.should include "Add-Ons Installed"
      @page.alert.text.should include addons[0...4].join(' ')
      @page.alert.text.should include "and #{addons[4..-1].count} others..."
      @page.should have_first_party_no_results
      @page.should_not have_first_party_pagination
      @page.should_not have_first_party_bulk_action
    end

    it 'displays an itemized modal when attempting to uninstall 5 or less add-on' do
      # First by installed
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "installed"
      no_php_js_errors

      addon_name = @page.first_party_addon_names[0].text

      # Header at 0, first "real" row is 1
      @page.first_party_addons[0].find('input[type="checkbox"]').set true
      @page.wait_until_first_party_bulk_action_visible
      @page.first_party_bulk_action.select "Uninstall"
      @page.first_party_action_submit_button.click

      @page.wait_until_modal_visible
      @page.modal_title.text.should eq "Confirm Uninstall"
      @page.modal.text.should include "You are attempting to uninstall the following items, please confirm this action."
      @page.modal.text.should include addon_name
      @page.modal.all('.checklist li').length.should eq 1
    end

    it 'displays a bulk confirmation modal when attempting to uninstall more than 5 add-ons' #do
    #   # First by installed
    #   @page.first_party_status_filter.click
    #   @page.wait_until_first_party_status_filter_menu_visible
    #   @page.first_party_status_filter_menu.click_link "installed"
    #   no_php_js_errors
    #
    #   @page.first_party_checkbox_header.find('input[type="checkbox"]').set true
    #   @page.wait_until_first_party_bulk_action_visible
    #   @page.first_party_bulk_action.select "Uninstall"
    #   @page.first_party_action_submit_button.click
    #
    #   @page.wait_until_modal_visible
    #   @page.modal_title.text.should eq "Confirm Uninstall"
    #   @page.modal.text.should include "You are attempting to uninstall the following items, please confirm this action."
    #   @page.modal.text.should include 'Add-On: 17 Add-Ons'
    # end

    it 'can uninstall add-ons' do
      # First by installed
      @page.first_party_status_filter.click
      @page.wait_until_first_party_status_filter_menu_visible
      @page.first_party_status_filter_menu.click_link "installed"
      no_php_js_errors

      addons = @page.first_party_addon_names.map {|addon| addon.text}
      @page.first_party_checkbox_header.find('input[type="checkbox"]').set true
      @page.wait_until_first_party_bulk_action_visible
      @page.first_party_bulk_action.select "Uninstall"
      @page.first_party_action_submit_button.click
      @page.wait_until_modal_visible
      @page.modal_submit_button.click # Submits a form
      no_php_js_errors

      # The filter should not change
      @page.first_party_status_filter.text.should eq "status (installed)"
      @page.should have_alert
      @page.alert.text.should include "Add-Ons Uninstalled"
      @page.alert.text.should include addons[0...4].join(' ')
      @page.alert.text.should include "and #{addons[4..-1].count} others..."
    end

    # The settings buttons "work" (200 response)
    it 'can navigate to a settings page' do
      @page.first('ul.toolbar li.settings a[title="Settings"]').click
      no_php_js_errors
    end

    # The guide buttons "work" (200 response)
    it 'can navigate to a manual page' do
      @page.first('ul.toolbar li.manual a[title="Manual"]').click
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

      @page.should have_third_party_addons
    end

    before(:each, :install => true) do
      @page.third_party_checkbox_header.find('input[type="checkbox"]').set true
      @page.wait_until_third_party_bulk_action_visible
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

      it 'can combine filters' do
        # First by installed
        @page.third_party_status_filter.click
        @page.wait_until_third_party_status_filter_menu_visible
        @page.third_party_status_filter_menu.click_link "uninstalled"
        no_php_js_errors

        @page.should have_css 'tr.not-installed'

        # Now by Developer
        @page.third_party_developer_filter.click
        @page.wait_until_third_party_developer_filter_menu_visible
        @page.third_party_developer_filter_menu.click_link "Test LLC"
        no_php_js_errors

        @page.third_party_status_filter.text.should eq "status (uninstalled)"
        @page.third_party_developer_filter.text.should eq 'developer (Test LLC)'
      end

      it 'can install a single add-on' do
        addon_name = @page.third_party_addon_names[0].text

        # Header at 0, first "real" row is 1
        @page.third_party_addons[0].find('ul.toolbar li.txt-only a.add').click
        no_php_js_errors

        # The filter should not change
        @page.should have_alert
        @page.alert.text.should include "Add-Ons Installed"
        @page.alert.text.should include addon_name
        @page.third_party_addons.should_not have_text addon_name
      end

      it 'can bulk-install add-ons' do
        addons = @page.third_party_addon_names.map {|addon| addon.text}

        @page.third_party_checkbox_header.find('input[type="checkbox"]').set true
        @page.wait_until_third_party_bulk_action_visible
        @page.third_party_bulk_action.select "Install"
        @page.third_party_action_submit_button.click
        no_php_js_errors

        # The filter should not change
        @page.should have_alert
        @page.alert.text.should include "Add-Ons Installed"
        @page.alert.text.should include addons[0...4].join(' ')
        @page.alert.text.should include "and #{addons[4..-1].count} others..."
      end

      it 'displays an itemized modal when attempting to uninstall 5 or less add-on', :install => true do
        # First by installed
        @page.third_party_status_filter.click
        @page.wait_until_third_party_status_filter_menu_visible
        @page.third_party_status_filter_menu.click_link "installed"
        no_php_js_errors

        addon_name = @page.third_party_addon_names[0].text

        # Header at 0, first "real" row is 1
        @page.third_party_addons[0].find('input[type="checkbox"]').set true
        @page.wait_until_third_party_bulk_action_visible
        @page.third_party_bulk_action.select "Uninstall"
        @page.third_party_action_submit_button.click

        @page.wait_until_modal_visible
        @page.modal_title.text.should eq "Confirm Uninstall"
        @page.modal.text.should include "You are attempting to uninstall the following items, please confirm this action."
        @page.modal.text.should include addon_name
        @page.modal.all('.checklist li').length.should eq 1
      end

      it 'displays a bulk confirmation modal when attempting to uninstall more than 5 add-ons', :install => true do
        # First by installed
        @page.third_party_status_filter.click
        @page.wait_until_third_party_status_filter_menu_visible
        @page.third_party_status_filter_menu.click_link "installed"
        no_php_js_errors

        @page.third_party_checkbox_header.find('input[type="checkbox"]').set true
        @page.wait_until_third_party_bulk_action_visible
        @page.third_party_bulk_action.select "Uninstall"
        @page.third_party_action_submit_button.click

        @page.wait_until_modal_visible
        @page.modal_title.text.should eq "Confirm Uninstall"
        @page.modal.text.should include "You are attempting to uninstall the following items, please confirm this action."
        @page.modal.text.should include 'Add-On: 6 Add-Ons'
      end

      it 'can uninstall add-ons', :install => true do
        # First by installed
        @page.third_party_status_filter.click
        @page.wait_until_third_party_status_filter_menu_visible
        @page.third_party_status_filter_menu.click_link "installed"
        no_php_js_errors

        addons = @page.third_party_addon_names.map {|addon| addon.text}
        @page.third_party_checkbox_header.find('input[type="checkbox"]').set true
        @page.wait_until_third_party_bulk_action_visible
        @page.third_party_bulk_action.select "Uninstall"
        @page.third_party_action_submit_button.click
        @page.wait_until_modal_visible
        @page.modal_submit_button.click # Submits a form
        no_php_js_errors

        # The filter should not change
        @page.third_party_status_filter.text.should eq "status (installed)"
        @page.should have_alert
        @page.alert.text.should include "Add-Ons Uninstalled"
        @page.alert.text.should include addons[0...4].join(' ')
        @page.alert.text.should include "and #{addons[4..-1].count} others..."
      end

      # The settings buttons "work" (200 response)
      # it 'can navigate to a settings page' do
      #   @page.phrase_search.set 'Rich Text Editor'
      #   @page.search_submit_button.click
      #   no_php_js_errors
      #
      #   @page.find('ul.toolbar li.settings a').click
      #   no_php_js_errors
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

      describe "keeps sort when paging the other table" do
        it "can sort First Party & page Third Party" do
          @page.first_party_version_header.find('a.sort').click
          no_php_js_errors

          @page.first_party_version_header[:class].should eq 'highlight'
        end

        it "can sort Third Party & page First Party" do
          @page.third_party_version_header.find('a.sort').click
          no_php_js_errors

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

      describe "keeps the filter when paging the other table" do
        it "can filter First Party & page Third Party" do
          @page.first_party_status_filter.click
          @page.wait_until_first_party_status_filter_menu_visible
          @page.first_party_status_filter_menu.click_link "installed"
          no_php_js_errors

          @page.first_party_status_filter.text.should eq "status (installed)"
        end

        it "can filter Third Party & page First Party" do
          @page.third_party_status_filter.click
          @page.wait_until_third_party_status_filter_menu_visible
          @page.third_party_status_filter_menu.click_link "uninstalled"
          no_php_js_errors

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
