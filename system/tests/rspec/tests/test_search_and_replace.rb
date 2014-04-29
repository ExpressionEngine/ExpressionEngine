require './bootstrap.rb'

feature 'Search and Replace' do

  before(:each) do
    cp_session
    @sandr = SearchAndReplace.new
    @sandr.load
    no_php_js_errors
  end

  it 'shows the Search and Replace page' do
    @sandr.should have_text 'Data Search and Replace'
    @sandr.should have_text 'Advanced users only.'
    @sandr.should have_search_term
    @sandr.should have_replace_term
    @sandr.should have_replace_where
    @sandr.should have_password_auth
    @sandr.should have_submit_button
  end

  it 'should invalidate on the fly' do
    @sandr.submit_enabled?.should eq true

    @sandr.search_term.trigger 'blur'
    @sandr.should have_text 'The "Search for this text" field is required.'
    @sandr.should have_no_text 'The "Replace with this text" field is required.'

    @sandr.submit_enabled?.should eq false

    page.should have_css 'fieldset.invalid'

    @sandr.replace_term.trigger 'blur'
    @sandr.should have_text 'The "Search for this text" field is required.'
    @sandr.should have_text 'The "Replace with this text" field is required.'

    @sandr.submit_enabled?.should eq false

    @sandr.search_term.set 'Text'
    @sandr.search_term.trigger 'blur'
    @sandr.should have_no_text 'The "Search for this text" field is required.'
    @sandr.should have_text 'The "Replace with this text" field is required.'

    @sandr.submit_enabled?.should eq false

    @sandr.replace_where.select 'Site Preferences (Choose from the following)'
    @sandr.should have_no_text 'The "Search for this text" field is required.'
    @sandr.should have_text 'The "Replace with this text" field is required.'
    @sandr.should have_text 'The "Search and replace in" field is required.'

    @sandr.submit_enabled?.should eq false

    @sandr.password_auth.trigger 'blur'
    @sandr.should have_text 'The "Current password" field is required.'

    @sandr.password_auth.set 'test'
    @sandr.password_auth.trigger 'blur'
    @sandr.should have_text 'The password entered is incorrect.'

    @sandr.password_auth.set 'password'
    @sandr.replace_term.set 'test'
    @sandr.replace_term.trigger 'blur'
    @sandr.replace_where.select 'Channel Entry Titles'

    @sandr.should have_no_text 'The "Search for this text" field is required.'
    @sandr.should have_no_text 'The "Replace with this text" field is required.'
    @sandr.should have_no_text 'The "Search and replace in" field is required.'
    @sandr.should have_no_text 'The "Current password" field is required.'
    @sandr.should have_no_text 'The password entered is incorrect.'

    page.should have_no_css 'fieldset.invalid'

    @sandr.submit_enabled?.should eq true

    no_php_js_errors

    @sandr.submit_button.click

    no_php_js_errors

    @sandr.should have_text 'Action was a success'
    @sandr.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should fail validation without AJAX too' do
    @sandr.submit_button.click

    page.should have_css 'fieldset.invalid'

    @sandr.should have_text 'An error occurred'
    @sandr.should have_text 'There was a problem processing your submission, please check below and fix all errors.'
    @sandr.should have_text 'The "Search for this text" field is required.'
    @sandr.should have_text 'The "Replace with this text" field is required.'
    @sandr.should have_text 'The "Search and replace in" field is required.'
    @sandr.should have_text 'The "Current password" field is required.'

    @sandr.submit_enabled?.should eq false

    no_php_js_errors

    @sandr.search_term.set 'Text'
    @sandr.search_term.trigger 'blur'
    @sandr.replace_term.set 'test'
    @sandr.replace_term.trigger 'blur'
    @sandr.replace_where.select 'Channel Entry Titles'
    @sandr.password_auth.set 'password'
    @sandr.password_auth.trigger 'blur'

    @sandr.should have_no_text 'The "Search for this text" field is required.'
    @sandr.should have_no_text 'The "Replace with this text" field is required.'
    @sandr.should have_no_text 'The "Search and replace in" field is required.'
    @sandr.should have_no_text 'The "Current password" field is required.'
    @sandr.should have_no_text 'The password entered is incorrect.'

    @sandr.should have_no_css 'fieldset.invalid'

    @sandr.submit_enabled?.should eq true

    no_php_js_errors

    @sandr.submit_button.click

    no_php_js_errors

    @sandr.should have_text 'Action was a success'
    @sandr.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should search and replace data' do

    @sandr.search_term.set 'Welcome'
    @sandr.replace_term.set 'test'
    @sandr.replace_where.select 'Channel Entry Titles'
    @sandr.password_auth.set 'password'

    @sandr.submit_button.click

    no_php_js_errors

    @sandr.should have_text 'Action was a success'
    @sandr.should have_text 'Number of database records in which a replacement occurred: 1'
  end

end