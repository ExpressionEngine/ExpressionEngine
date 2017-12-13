require './bootstrap.rb'

feature 'System Templates' do
  before(:each) do
    cp_session
    @page = SystemTemplates.new
    @form = SystemTemplateForm.new
  end

  context 'Messages' do
    before(:each) do
      @page.load('system')
      no_php_js_errors
    end

    it 'displays' do
      @page.templates.should have(2).items
      @page.templates[0].name.text.should eq 'Site Offline'
      @page.templates[1].name.text.should eq 'User Messages'
    end

    it 'displays the edit form' do
      @page.templates[0].manage.edit.click
      no_php_js_errors
      @form.all_there?.should == true
      @form.template_contents.value.should_not eq ''
    end
  end

  context 'Email' do
    before(:each) do
      @page.load('email')
      no_php_js_errors
    end

    it 'displays' do
      @page.templates.should have(15).items
    end

    it 'displays the edit form' do
      @page.templates[1].manage.edit.click
      no_php_js_errors
      @form.all_there?.should == true
      @form.template_contents.value.should_not eq ''
    end
  end

  context 'Members' do
    before(:each) do
      @page.load('members')
      no_php_js_errors
    end

    it 'displays' do
      @page.should have_theme_chooser
      @page.templates.should have(86).items
    end

    it 'displays the edit form' do
      @page.templates[1].manage.edit.click
      no_php_js_errors
      @form.all_there?.should == true
      @form.template_contents.value.should_not eq ''
    end
  end

  # context 'Forums' do
  #   before(:each) do
  #     @page.load('forums')
  #     no_php_js_errors
  #   end
  #
  #   it 'displays' do
  #     @page.should have_theme_chooser
  #     @page.templates.should have(201).items
  #
  #     it 'displays the edit form' do
  #       @page.templates[1].manage.edit.click
  #       no_php_js_errors
  #       @form.all_there?.should == true
  #       @form.template_contents.value.should_not eq ''
  #     end
  #   end
  # end
end
