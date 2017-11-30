require './bootstrap.rb'

feature 'Template Manager' do
  before(:each) do
    ee_config(item: 'save_tmpl_files', value: 'n')
    cp_session
    @page = TemplateManager.new
  end

  it 'displays' do
    @page.load
    no_php_js_errors
    @page.all_there?.should == true
    @page.templates.should have(6).items
  end

  context 'Template Groups' do
    it 'can add a template group' do
      form = TemplateGroupCreate.new
      form.load

      no_php_js_errors

      form.name.set 'rspec-test'
      form.save_button.click

      no_php_js_errors

      @page.should have_alert
      @page.alert.text.should include 'Template Group Created'
      @page.alert.text.should include 'rspec-test'
      @page.page_title.text.should eq 'Templates in rspec-test'
      @page.templates.should have(1).items
      @page.templates[0].name.text.should eq 'index'
    end

    it 'can duplicate an existing template group' do
      form = TemplateGroupCreate.new
      form.load

      no_php_js_errors

      form.name.set 'rspec-test'
      form.duplicate_existing_group.choose_radio_option('1') # about
      form.save_button.click

      no_php_js_errors

      @page.should have_alert
      @page.alert.text.should include 'Template Group Created'
      @page.alert.text.should include 'rspec-test'
      @page.page_title.text.should eq 'Templates in rspec-test'
      @page.templates.should have(3).items

      @page.templates[0].name.text.should eq '404'
      @page.templates[1].name.text.should eq 'contact'
      @page.templates[2].name.text.should eq 'index'
    end

    it 'can edit a template group' do
      form = TemplateGroupEdit.new
      form.load_edit_for_group('about')

      no_php_js_errors

      form.name.set 'rspec-test'
      form.save_button.click

      no_php_js_errors

      @page.should have_alert
      @page.alert.text.should include 'Template Group Updated'
      @page.alert.text.should include 'rspec-test'
      @page.page_title.text.should eq 'Templates in rspec-test'
      @page.templates.should have(3).items

      @page.templates[0].name.text.should eq '404'
      @page.templates[1].name.text.should eq 'contact'
      @page.templates[2].name.text.should eq 'index'
    end

    it 'should validate the form' do
      form = TemplateGroupCreate.new
      form.load

      no_php_js_errors

      form.name.set 'about'
      form.name.trigger 'blur'
      form.wait_for_error_message_count(1)
      should_have_error_text(form.name, 'The template group name you submitted is already taken')
      should_have_form_errors(form)
    end

    it 'remove a template group' do
      @page.load
      no_php_js_errors

      @page.template_groups.should have(4).items
      @page.template_groups[0].remove.click
      @page.wait_for_modal_submit_button
      @page.modal_submit_button.click

      no_php_js_errors

      @page.should have_alert
      @page.alert.text.should include 'Template Group Removed'
      @page.alert.text.should include 'about'
      @page.template_groups.should have(3).items
    end

    it 'can change the template group view' do
      @page.load
      no_php_js_errors

      @page.page_title.text.should eq 'Templates in news'
      @page.templates.should have(6).items
      @page.template_groups[0].name.click

      no_php_js_errors

      @page.page_title.text.should eq 'Templates in about'
      @page.templates.should have(3).items

      @page.templates[0].name.text.should eq '404'
      @page.templates[1].name.text.should eq 'contact'
      @page.templates[2].name.text.should eq 'index'
    end

    it 'can change the default group' do
      @page.load
      no_php_js_errors

      @page.default_template_group.text.should eq 'news'

      @page.template_groups[0].edit.click

      no_php_js_errors

      form = TemplateGroupEdit.new
      form.make_default_group.click
      form.save_button.click

      no_php_js_errors

      @page.default_template_group.text.should eq 'about'
    end
  end

  context 'Templates' do
    before(:each) do
      @page.load
      no_php_js_errors
    end

    it 'can view a template' do
      template_group = @page.active_template_group.text
      template = @page.templates[0].name.text

      visit(@page.templates[0].manage.view[:href])

      no_php_js_errors

      @page.text.should include "#{template_group}/#{template}"
    end

    it 'can change the settings for a template' do
      @page.templates[0].manage.settings.click
      @page.wait_for_modal

      form = TemplateEdit.new
      form.name.set 'archives-and-stuff'
      form.type.choose_radio_option('feed')
      form.enable_caching.click
      form.refresh_interval.set '5'
      form.allow_php.click
      form.php_parse_stage.choose_radio_option('i')
      form.hit_counter.set '10'

      find('.modal form .form-btns-top input.btn[type="submit"]').click

      @page.templates[0].manage.settings.click
      @page.wait_for_modal

      form.name.value.should eq 'archives-and-stuff'
      form.type.has_checked_radio('feed')
      form.enable_caching[:class].should include 'on'
      form.refresh_interval.value.should eq '5'
      form.allow_php[:class].should include 'on'
      form.php_parse_stage.has_checked_radio('i')
      form.hit_counter.value.should eq '10'
    end

    it 'should validate the settings form' do
      @page.templates[0].manage.settings.click
      @page.wait_for_modal

      form = TemplateEdit.new
      form.name.set 'archives and stuff'
      form.name.trigger 'blur'
      form.wait_for_error_message_count(1)
      should_have_error_text(form.name, 'This field may only contain alpha-numeric characters, underscores, dashes, periods, and emojis.')
      should_have_form_errors(form)
    end

    it 'can export some templates' do
      skip "need to handle download via POST" do
      end
    end

    it 'can remove a template' do
      @page.templates[0].bulk_action_checkbox.click
      @page.wait_for_bulk_action

      @page.has_bulk_action?.should == true
      @page.has_action_submit_button?.should == true

      @page.bulk_action.select 'Remove'
      @page.action_submit_button.click

      @page.wait_for_modal_submit_button
      @page.modal_submit_button.click

      no_php_js_errors

      @page.should have_alert
      @page.alert[:class].should include 'success'
      @page.templates.should have(5).items
    end
  end

  it 'can export all templates' do
    @page.load
    no_php_js_errors

    url = @page.export_icon[:href]

    @page.execute_script("window.downloadCSVXHR = function(){ var url = '#{url}'; return getFile(url); }")
    @page.execute_script('window.getFile = function(url) { var xhr = new XMLHttpRequest();  xhr.open("GET", url, false);  xhr.send(null); return xhr.responseText; }')
    data = @page.evaluate_script('downloadCSVXHR()')
    data.should start_with('PK')
  end

  it 'can search templates' do
    @page.load
    no_php_js_errors

    @page.phrase_search.set 'Recent News'
    @page.search_submit_button.click

    no_php_js_errors

    @page.page_title.text.should include "Search Results"
    @page.templates.should have(4).items
  end

end
