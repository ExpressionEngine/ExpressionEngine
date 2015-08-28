require './bootstrap.rb'

feature 'License Settings' do

  before(:each) do
    cp_session
    @page = LicenseSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the License settings page'

end