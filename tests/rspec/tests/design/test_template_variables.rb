require './bootstrap.rb'

feature 'Template Variables' do
  before(:each) do
    cp_session
    @page = TemplateVariables.new
    @page.load
    no_php_js_errors
  end

  it 'displays' do
    @page.all_there?.should == true
  end
end
