require './bootstrap.rb'

feature 'Template Manager' do
  before(:each) do
    cp_session
    @page = TemplateManager.new
  end

  it 'displays' do
    @page.load
    no_php_js_errors
    @page.all_there?.should == true
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

end
