require './bootstrap.rb'

feature 'Profile - Personal Settings' do
  before(:each) do
    cp_session
    @page = Profile::PersonalSettings.new
    @page.load
    no_php_js_errors
  end

  it 'should load' do
    @page.all_there?.should == true
  end
end
