require './bootstrap.rb'

feature 'License Settings' do

  before(:each) do
    cp_session
    @page = LicenseSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the License settings page' do
    @page.should have_text 'License & Registration Settings'
    @page.all_there?.should == true
  end

end