require './bootstrap.rb'

feature 'Publish Page - Edit' do
  before :each do
    cp_session
    @page = Publish.new
    no_php_js_errors
  end

  it 'shows a 404 with no given entry_id' do
    @page.load
    @page.is_404?.should == true
  end
end
