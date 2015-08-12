require './bootstrap.rb'

feature 'Cache Manager' do

  before(:each) do
    cp_session
    CacheManager::visit
  end

  it 'shows the Cache Manager page' do
    page.should have_text 'Cache Manager'
    page.should have_text 'Caches to clear'
    page.should have_checked_field 'All Caches'
    page.should have_no_text 'An error occurred'
  end

  it 'should successfully submit with one cache type selected' do
    CacheManager::button.click
    no_php_js_errors

    page.should have_text 'Caches cleared'
  end
end
