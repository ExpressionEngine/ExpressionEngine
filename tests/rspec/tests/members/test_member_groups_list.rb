require './bootstrap.rb'

feature 'Member Group List' do

  before(:each) do
    cp_session
    @page = MemberGroups.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Member Group List page' do
    @page.all_there?.should == true
    @page.list.all_there?.should == true
  end

  it 'creates a Member Group' do
    @page.new_group.click
    @page.edit.all_there?.should == true
  end
end
