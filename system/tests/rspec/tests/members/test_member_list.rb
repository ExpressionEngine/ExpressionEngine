require './bootstrap.rb'

feature 'Member List' do

  before(:each) do
    cp_session
    @page = Members.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member List page' do
    @page.all_there?.should == true
  end
end
