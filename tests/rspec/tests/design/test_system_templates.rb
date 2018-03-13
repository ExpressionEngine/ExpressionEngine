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

  context 'Members without Templates' do
    before(:each) do
      @page.load('members')
      no_php_js_errors
    end

    it 'displays a helpful error when user templates are missing' do
      @page.should have_theme_chooser
      @page.templates.should have(1).items
      @page.templates[0].name.text.should start_with('Templates not found in themes/user')
    end
  end

  context 'Members with Templates' do
    before(:each) do
      @themes = Themes::Prepare.new
      @themes.copy_member_themes
      @page.load('members')
      no_php_js_errors
    end

    it 'displays when user templates are present' do
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

  context 'Forums without Templates' do
    before(:each) do
      visit '/system/index.php?/cp/addons'
      find('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click

      @page = SystemTemplates.new
      @form = SystemTemplateForm.new
      @page.load('forums')
      no_php_js_errors
    end

    it 'displays a helpful error when user templates are missing' do
      @page.should have_theme_chooser
      @page.templates.should have(1).items
      @page.templates[0].name.text.should start_with('Templates not found in themes/user')
    end
  end

  context 'Forums with Templates' do
    before(:each) do
      @themes = Themes::Prepare.new
      @themes.copy_forum_themes

      visit '/system/index.php?/cp/addons'
      find('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click

      @page = SystemTemplates.new
      @form = SystemTemplateForm.new
      @page.load('forums')
      no_php_js_errors
    end

    it 'displays when user templates are present' do
      @page.should have_theme_chooser
      @page.templates.should have(201).items
    end

    it 'displays the edit form' do
      @page.templates[1].manage.edit.click
      no_php_js_errors
      @form.all_there?.should == true
      @form.template_contents.value.should_not eq ''
    end
  end
end
