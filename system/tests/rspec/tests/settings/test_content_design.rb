require './bootstrap.rb'

feature 'Content & Design Settings' do

  before(:each) do
    cp_session
    @page = ContentDesign.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Content & Design Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    new_posts_clear_caches = ee_config(item: 'new_posts_clear_caches')
    enable_sql_caching = ee_config(item: 'enable_sql_caching')
    auto_assign_cat_parents = ee_config(item: 'auto_assign_cat_parents')

    @page.new_posts_clear_caches_y.checked?.should == (new_posts_clear_caches == 'y')
    @page.new_posts_clear_caches_n.checked?.should == (new_posts_clear_caches == 'n')
    @page.enable_sql_caching_y.checked?.should == (enable_sql_caching == 'y')
    @page.enable_sql_caching_n.checked?.should == (enable_sql_caching == 'n')
    @page.auto_assign_cat_parents_y.checked?.should == (auto_assign_cat_parents == 'y')
    @page.auto_assign_cat_parents_n.checked?.should == (auto_assign_cat_parents == 'n')
  end

  it 'should save and load the settings' do
    @page.new_posts_clear_caches_n.click
    @page.enable_sql_caching_y.click
    @page.auto_assign_cat_parents_n.click
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.new_posts_clear_caches_n.checked?.should == true
    @page.enable_sql_caching_y.checked?.should == true
    @page.auto_assign_cat_parents_n.checked?.should == true
  end
end