require './bootstrap.rb'

feature 'Search and Replace' do

  before(:each) do
    cp_session
    @page = SearchAndReplace.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Search and Replace page' do
    @page.should have_text 'Data Search and Replace'
    @page.should have_text 'Advanced users only.'
    @page.should have_search_term
    @page.should have_replace_term
    @page.should have_replace_where
    @page.should have_password_auth
    @page.should have_submit_button
  end

  it 'should validate the form' do
    @page.submit_enabled?.should eq true

    @page.search_term.trigger 'blur'
    @page.should have_text 'The "Search for this text" field is required.'
    @page.should have_no_text 'The "Replace with this text" field is required.'

    @page.submit_enabled?.should eq false

    page.should have_css 'fieldset.invalid'

    @page.replace_term.trigger 'blur'
    @page.should have_text 'The "Search for this text" field is required.'
    @page.should have_text 'The "Replace with this text" field is required.'

    @page.submit_enabled?.should eq false

    @page.search_term.set 'Text'
    @page.search_term.trigger 'blur'
    @page.should have_no_text 'The "Search for this text" field is required.'
    @page.should have_text 'The "Replace with this text" field is required.'

    @page.submit_enabled?.should eq false

    @page.replace_where.select 'Site Preferences (Choose from the following)'
    @page.should have_no_text 'The "Search for this text" field is required.'
    @page.should have_text 'The "Replace with this text" field is required.'
    @page.should have_text 'The "Search and replace in" field is required.'

    @page.submit_enabled?.should eq false

    @page.password_auth.trigger 'blur'
    @page.should have_text 'The "Current password" field is required.'

    @page.password_auth.set 'test'
    @page.password_auth.trigger 'blur'
    @page.should have_text 'The password entered is incorrect.'

    @page.password_auth.set 'password'
    @page.replace_term.set 'test'
    @page.replace_term.trigger 'blur'
    @page.replace_where.select 'Channel Entry Titles'

    @page.should have_no_text 'The "Search for this text" field is required.'
    @page.should have_no_text 'The "Replace with this text" field is required.'
    @page.should have_no_text 'The "Search and replace in" field is required.'
    @page.should have_no_text 'The "Current password" field is required.'
    @page.should have_no_text 'The password entered is incorrect.'

    page.should have_no_css 'fieldset.invalid'

    @page.submit_enabled?.should eq true

    no_php_js_errors

    @page.submit_button.click

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should fail validation without AJAX too' do
    @page.submit_button.click

    page.should have_css 'fieldset.invalid'

    @page.should have_text 'An error occurred'
    @page.should have_text 'There was a problem processing your submission, please check below and fix all errors.'
    @page.should have_text 'The "Search for this text" field is required.'
    @page.should have_text 'The "Replace with this text" field is required.'
    @page.should have_text 'The "Search and replace in" field is required.'
    @page.should have_text 'The "Current password" field is required.'

    @page.submit_enabled?.should eq false

    no_php_js_errors

    @page.search_term.set 'Text'
    @page.search_term.trigger 'blur'
    @page.replace_term.set 'test'
    @page.replace_term.trigger 'blur'
    @page.replace_where.select 'Channel Entry Titles'
    @page.password_auth.set 'password'
    @page.password_auth.trigger 'blur'

    @page.should have_no_text 'The "Search for this text" field is required.'
    @page.should have_no_text 'The "Replace with this text" field is required.'
    @page.should have_no_text 'The "Search and replace in" field is required.'
    @page.should have_no_text 'The "Current password" field is required.'
    @page.should have_no_text 'The password entered is incorrect.'

    @page.should have_no_css 'fieldset.invalid'

    @page.submit_enabled?.should eq true

    no_php_js_errors

    @page.submit_button.click

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should search and replace data' do

    @page.search_term.set 'Welcome'
    @page.replace_term.set 'test'
    @page.replace_where.select 'Channel Entry Titles'
    @page.password_auth.set 'password'

    @page.submit_button.click

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 1'
  end

end