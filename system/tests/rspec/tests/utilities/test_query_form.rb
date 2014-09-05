require './bootstrap.rb'

feature 'Query Form' do

  before(:each) do
    cp_session
    @page = QueryForm.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Query Form' do
    @page.should have_text 'Query to run'
    @page.should have_query_form
    @page.should have_show_errors
    @page.should have_password
  end

  it 'should validate the form' do
    field_required = 'This field is required.'
    form_error = 'Attention: Query not run'
    password_incorrect = 'The password entered is incorrect.'

    # Submit with nothing
    @page.submit

    no_php_js_errors
    @page.should have_text form_error
    should_have_error_text(@page.query_form, field_required)
    should_have_error_text(@page.password, field_required)
    @page.should have_no_text password_incorrect
    should_have_form_errors(@page)

    # Query but no password
    @page.load
    @page.query_form.set 'query'
    @page.submit

    no_php_js_errors
    @page.should have_text form_error
    should_have_no_error_text(@page.query_form)
    should_have_error_text(@page.password, field_required)
    @page.should have_no_text password_incorrect
    should_have_form_errors(@page)

    # Query with wrong password
    @page.load
    @page.query_form.set 'query'
    @page.password.set 'test'
    @page.submit

    no_php_js_errors
    @page.query_form.value.should eq 'query'
    should_have_no_error_text(@page.query_form)
    should_have_error_text(@page.password, password_incorrect)
    should_have_form_errors(@page)

    # AJAX Validation
    @page.load
    @page.query_form.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.query_form, field_required)
    should_have_no_error_text(@page.password)

    @page.password.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.query_form, field_required)
    should_have_error_text(@page.password, field_required)

    @page.password.set 'pass'
    @page.password.trigger 'blur'
    should_have_error_text(@page.query_form, field_required)
    should_have_error_text(@page.password, password_incorrect)

    @page.password.set 'password'
    @page.password.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.query_form, field_required)
    should_have_no_error_text(@page.password)

    @page.query_form.set 'SELECT'
    @page.query_form.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.query_form)
    should_have_no_error_text(@page.password)
  end

  it 'should not allow certain query types' do
    not_allowed = 'Query type not allowed'

    @page.query_form.set "FLUSH TABLES"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "REPLACE INTO offices(officecode,city) VALUES(8,'San Jose')"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "GRANT ALL ON db1.* TO 'jeffrey'@'localhost'"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "REVOKE INSERT ON *.* FROM 'jeffrey'@'localhost'"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "LOCK TABLES t1 READ"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "UNLOCK TABLES t1 READ"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "SELECT * FROM exp_channels"
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    @page.should have_no_text not_allowed
  end

  it 'should conditionally show MySQL errors' do
    error_text = 'You have an error in your SQL syntax'

    # Invalid query with errors on
    @page.query_form.set "SELECT FROM exp_channels"
    @page.password.set 'password'
    @page.submit

    @page.should have_text 'Attention: Query not run'
    @page.should have_text error_text

    cp_session
    @page.load
    @page.query_form.set "SELECT FROM exp_channels"
    @page.show_errors.click # Uncheck error showing
    @page.password.set 'password'
    @page.submit

    @page.should have_no_text error_text
    @page.should have_text 'Total Results: 0'
    @page.should have_text 'No rows returned'
  end

  it 'should show query results' do
    @page.query_form.set 'SELECT * FROM exp_channels'
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have_text 'Query FormQuery Results' # How Capybara sees the breadcrumb
    results.should have_text 'SELECT * FROM exp_channels'
    results.should have_text 'Total Results: 2'
    results.should have_no_text 'No rows returned'

    results.should have(0).pages
    results.should have(3).rows # 2 results plus header
    results.table.should have_text 'channel_id'
    results.table.should have_text 'site_id'
    results.table.should have_text 'channel_name'
    results.table.should have_text 'News'
    results.table.should have_text 'Information Pages'
  end

  it 'should sort query results by columns' do
    @page.query_form.set 'SELECT * FROM exp_channels'
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.sort_links[0].click # Sort by channel_id descending
    results.table.find('tr:nth-child(2) td:nth-child(1)').should have_text '2'
    results.table.find('tr:nth-child(3) td:nth-child(1)').should have_text '1'
  end

  it 'should search query results' do
    @page.query_form.set 'select * from exp_channel_titles'
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have(0).pages
    results.should have(11).rows # 10 results plus header

    results.search_field.set 'the'
    results.search_btn.click

    no_php_js_errors
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(3).rows # 2 results plus header
    results.table.find('tr:nth-child(3) td:nth-child(7)').should have_text 'About the Label'

    # Make sure we can still sort and maintain search results
    results.sort_links[0].click
    no_php_js_errors
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(3).rows # 2 results plus header
    # This should be in the next row down now
    results.table.find('tr:nth-child(2) td:nth-child(7)').should have_text 'About the Label'
  end

  it 'should paginate query results' do
    # Generate random data that will paginate
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    @page.query_form.set 'select * from exp_cp_log'
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have(21).rows # 20 results plus header
    results.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
    click_link "Next"

    no_php_js_errors
    results.should have(12).rows # 11 results plus header
    results.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  end

  it 'should paginate sorted query results' do
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    @page.query_form.set 'select * from exp_cp_log'
    @page.password.set 'password'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.sort_links[0].click
    results.table.find('tr:nth-child(2) td:nth-child(1)').should have_text '31'

    no_php_js_errors
    click_link "Next"

    results.table.find('tr:nth-child(2) td:nth-child(1)').should have_text '11'
  end

  it 'should show no results when there are no results' do
    @page.query_form.set 'select * from exp_channels where channel_id = 1000'
    @page.password.set 'password'
    @page.submit

    @page.should have_text 'Total Results: 0'
    @page.should have_text 'No rows returned'
  end

  it 'should show the number of affected rows on write queries' do
    @page.query_form.set 'UPDATE exp_channel_titles SET title = "Kevin" WHERE title = "Josh"'
    @page.password.set 'password'
    @page.submit

    @page.should have_text 'Affected Rows: 1'
    @page.should have_no_text 'Total Results: 0'
    @page.should have_text 'No rows returned'
  end
end