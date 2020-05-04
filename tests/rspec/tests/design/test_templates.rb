require './bootstrap.rb'

feature 'Templates' do
  before(:each) do
    ee_config(item: 'save_tmpl_files', value: 'n')
    cp_session
  end

  context 'Creating a template' do
    before(:each) do
      @page = TemplateCreate.new
      @page.load
      no_php_js_errors
    end

    it 'displays the create form' do
      @page.all_there?.should == true
    end

    it 'can create a new template' do
      @page.name.set 'rspec-test'
      @page.save_button.click

      no_php_js_errors

      manager = TemplateManager.new
      manager.all_there?.should == true
      manager.templates.should have(7).items
    end

    it 'can show the edit form after save' do
      @page.name.set 'rspec-test'
      @page.save_and_edit_button.click

      no_php_js_errors

      form = TemplateEdit.new
      form.all_there?.should == true
    end

    it 'new templates have sensible defaults' do
      @page.name.set 'rspec-test'
      @page.save_and_edit_button.click

      no_php_js_errors

      form = TemplateEdit.new

      form.settings_tab.click
      form.name.value.should eq 'rspec-test'
      form.type.has_checked_radio('webpage').should == true
      form.enable_caching[:class].should include "off"
      form.refresh_interval.value.should eq '0'
      form.allow_php[:class].should include "off"
      form.php_parse_stage.has_checked_radio('o').should == true
      form.hit_counter.value.should eq '0'

      form.access_tab.click
      # Says "radio" but works with checkboxes
      form.allowed_roles.has_checked_radio('2').should == true
      form.allowed_roles.has_checked_radio('3').should == true
      form.allowed_roles.has_checked_radio('4').should == true
      form.allowed_roles.has_checked_radio('5').should == true
      form.no_access_redirect.each_with_index do |el, i|
        # Only "None" should be selected
        el.checked?.should == (i == 0)
      end
      form.enable_http_auth[:class].should include "off"
      form.template_route.value.should eq ''
      form.require_all_variables[:class].should include "off"
    end

    it 'can duplicate an existing template' do
      @page.name.set 'rspec-test'
      @page.duplicate_existing_template.choose_radio_option('11')
      @page.save_and_edit_button.click

      no_php_js_errors

      form = TemplateEdit.new
      form.template_data.value.should include "News Archives"
    end

    it 'should validate the form' do
      @page.name.set 'lots of neat stuff'
      @page.name.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_error_text(@page.name, 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')
      should_have_form_errors(@page)
    end

  end

  context 'Editing a template' do
    before(:each) do
      @page = TemplateEdit.new
      @page.load_edit_for_template('11')
      no_php_js_errors
    end

    it 'displays the edit form' do
      @page.all_there?.should == true

      @page.notes_tab.click
      @page.should have_template_notes

      @page.settings_tab.click
      @page.should have_name
      @page.should have_type
      @page.should have_enable_caching
      @page.should have_refresh_interval
      @page.should have_allow_php
      @page.should have_php_parse_stage
      @page.should have_hit_counter

      @page.access_tab.click
      @page.should have_allowed_roles
      @page.should have_no_access_redirect
      @page.should have_enable_http_auth
      @page.should have_template_route
      @page.should have_require_all_variables
    end

    it 'should validate the form' do
      @page.settings_tab.click
      @page.name.set 'lots of neat stuff'
      @page.name.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_error_text(@page.name, 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')
    end

    it 'can change settings' do
      @page.settings_tab.click
      @page.name.set 'rspec-edited'
      @page.type.choose_radio_option('feed')
      @page.enable_caching.click
      @page.refresh_interval.set '5'
      @page.allow_php.click
      @page.php_parse_stage.choose_radio_option('i')
      @page.hit_counter.set '10'

      @page.access_tab.click
      @page.allowed_roles[0].set(false)
      @page.no_access_redirect.choose_radio_option('27')
      @page.enable_http_auth.click
      @page.template_route.set 'et/phone/home'
      @page.require_all_variables.click

      @page.save_button.click

      no_php_js_errors

      @page.settings_tab.click
      @page.name.value.should eq 'rspec-edited'
      @page.type.has_checked_radio('feed')
      @page.enable_caching[:class].should include 'on'
      @page.refresh_interval.value.should eq '5'
      @page.allow_php[:class].should include 'on'
      @page.php_parse_stage.has_checked_radio('i')
      @page.hit_counter.value.should eq '10'

      @page.access_tab.click
      @page.allowed_roles.has_checked_radio('2').should == false
      @page.allowed_roles.has_checked_radio('3').should == true
      @page.allowed_roles.has_checked_radio('4').should == true
      @page.allowed_roles.has_checked_radio('5').should == true
      @page.no_access_redirect.has_checked_radio('27')
      @page.enable_http_auth[:class].should include 'on'
      @page.template_route.value.should eq 'et/phone/home'
      @page.require_all_variables[:class].should include 'on'
    end

    it 'stays on the edit page with the "save" button' do
      @page.save_button.click

      no_php_js_errors

      @page.all_there?.should == true

      @page.notes_tab.click
      @page.should have_template_notes

      @page.settings_tab.click
      @page.should have_name
      @page.should have_type
      @page.should have_enable_caching
      @page.should have_refresh_interval
      @page.should have_allow_php
      @page.should have_php_parse_stage
      @page.should have_hit_counter

      @page.access_tab.click
      @page.should have_allowed_roles
      @page.should have_no_access_redirect
      @page.should have_enable_http_auth
      @page.should have_template_route
      @page.should have_require_all_variables
     end

    it 'returns to the template manager with the "save & close" button' do
      @page.save_and_close_button.click

      no_php_js_errors

      manager = TemplateManager.new
      manager.all_there?.should == true
    end

  end

end
