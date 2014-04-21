require './bootstrap.rb'

feature 'Cache Manager' do

  before(:each) do
    cp_session
    CacheManager::visit
  end

  it 'shows the Cache Manager page' do
    page.should have_text 'Cache Manager'
    page.should have_text 'Caches to clear'
    page.should have_checked_field 'All'
    page.should have_checked_field 'All'
    page.should have_no_text 'An error occurred'
  end

  it 'should successfully submit with one cache type selected' do
    CacheManager::button.click
    no_php_js_errors

    page.should have_text 'Action was a success'
    page.should have_text 'Caches cleared.'
  end

  it 'should show an error if no cache types are selected before submit' do
    page.uncheck 'All'

    page.should have_text 'An error occurred'
    page.should have_text 'There was a problem processing your submission, please check below and fix all errors.'
    page.should have_text 'The Caches to clear field is required.'
    page.should have_css 'fieldset.invalid'

    CacheManager::button.value.should eq 'Fix Errors, Please'
    CacheManager::button[:disabled].should eq 'true'

    page.check 'All'

    page.should have_no_text 'An error occurred'
    page.should have_no_text 'There was a problem processing your submission, please check below and fix all errors.'
    page.should have_no_text 'The Caches to clear field is required.'
    page.should have_no_css 'fieldset.invalid'

    CacheManager::button.value.should eq 'Clear Caches'
    CacheManager::button[:disabled].should eq nil

    no_php_js_errors
  end

end