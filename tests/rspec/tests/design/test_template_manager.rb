require './bootstrap.rb'

feature 'Template Manager' do
  before(:each) do
    cp_session
    @page = TemplateManager.new
    @page.load
    no_php_js_errors
  end

  it 'displays' do
    @page.all_there?.should == true
  end
end
