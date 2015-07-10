require './bootstrap.rb'

feature 'Member Field List' do

  before(:each) do
    cp_session
    @page = MemberFields.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Field List page'
  # do
  #   @page.all_there?.should == true
  # end
end
