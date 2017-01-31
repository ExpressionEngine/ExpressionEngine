require './bootstrap.rb'

feature 'Search and Replace' do

  before(:each) do
    cp_session
    @page = SearchAndReplace.new
    @page.load
    no_php_js_errors

    @field_required = 'This field is required.'
  end

  it 'shows the Search and Replace page' do
    @page.should have_text 'Data Search and Replace'
    @page.should have_text 'Advanced users only.'
    @page.all_there?.should == true
  end

  it 'should validate the form' do
    @page.submit_enabled?.should eq true

    @page.search_term.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.search_term, @field_required)
    should_have_form_errors(@page)

    @page.search_term.set 'Text'
    @page.search_term.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.search_term)
    should_have_no_form_errors(@page)

    @page.replace_where.select 'Site Preferences (Choose from the following)'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.search_term)
    should_have_error_text(@page.replace_where, @field_required)
    should_have_form_errors(@page)

    @page.password_auth.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.search_term)
    should_have_error_text(@page.replace_where, @field_required)
    should_have_error_text(@page.password_auth, @field_required)
    should_have_form_errors(@page)

    @page.password_auth.set 'password'
    @page.password_auth.trigger 'blur'
    @page.wait_for_error_message_count(1)

    @page.password_auth.set 'test'
    @page.password_auth.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.search_term)
    should_have_error_text(@page.replace_where, @field_required)
    should_have_error_text(@page.password_auth, 'The password entered is incorrect.')
    should_have_form_errors(@page)

    @page.password_auth.set 'password'
    @page.password_auth.trigger 'blur'
    @page.wait_for_error_message_count(1)
    @page.replace_where.select 'Channel Entry Titles'
    @page.wait_for_error_message_count(0)

    should_have_no_error_text(@page.search_term)
    should_have_no_error_text(@page.replace_where)
    should_have_no_error_text(@page.replace_term)
    should_have_no_error_text(@page.password_auth)
    should_have_no_form_errors(@page)

    no_php_js_errors

    @page.submit

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should fail validation without AJAX too' do
    @page.submit

    @page.should have_text 'Attention: Search and replace not run'
    should_have_error_text(@page.search_term, @field_required)
    should_have_error_text(@page.replace_where, @field_required)
    should_have_error_text(@page.password_auth, @field_required)
    should_have_form_errors(@page)

    no_php_js_errors

    @page.search_term.set 'Text'
    @page.search_term.trigger 'blur'
    @page.wait_for_error_message_count(2)
    @page.replace_where.select 'Channel Entry Titles'
    @page.wait_for_error_message_count(1)
    @page.password_auth.set 'password'
    @page.password_auth.trigger 'blur'
    @page.wait_for_error_message_count(0)

    should_have_no_error_text(@page.search_term)
    should_have_no_error_text(@page.replace_where)
    should_have_no_error_text(@page.replace_term)
    should_have_no_error_text(@page.password_auth)
    should_have_no_form_errors(@page)

    no_php_js_errors

    @page.submit

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 0'
  end

  it 'should search and replace data' do

    @page.search_term.set 'Welcome'
    @page.replace_term.set 'test'
    @page.replace_where.select 'Channel Entry Titles'
    @page.password_auth.set 'password'

    @page.submit

    no_php_js_errors

    @page.should have_text 'Action was a success'
    @page.should have_text 'Number of database records in which a replacement occurred: 1'
  end

end
