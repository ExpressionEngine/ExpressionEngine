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
  end

  it 'should validate the form' do
    field_required = 'This field is required.'
    form_error = 'Attention: Query not run'

    # Submit with nothing
    @page.submit

    no_php_js_errors
    @page.should have_text form_error
    should_have_error_text(@page.query_form, field_required)
    should_have_form_errors(@page)

    # AJAX Validation
    @page.load
    @page.query_form.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.query_form, field_required)

    @page.query_form.set 'SELECT'
    @page.query_form.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.query_form)
  end

  it 'should not allow certain query types' do
    not_allowed = 'Query type not allowed'

    @page.query_form.set "FLUSH TABLES"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "REPLACE INTO offices(officecode,city) VALUES(8,'San Jose')"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "GRANT ALL ON db1.* TO 'jeffrey'@'localhost'"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "REVOKE INSERT ON *.* FROM 'jeffrey'@'localhost'"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "LOCK TABLES t1 READ"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "UNLOCK TABLES t1 READ"
    @page.submit

    no_php_js_errors
    @page.should have_text not_allowed

    @page.query_form.set "SELECT * FROM exp_channels"
    @page.submit

    no_php_js_errors
    @page.should have_no_text not_allowed
  end

  it 'should show MySQL errors' do
    error_text = 'You have an error in your SQL syntax'

    # Invalid query with errors on
    @page.query_form.set "SELECT FROM exp_channels"
    @page.submit

    no_php_js_errors
    @page.should have_text 'Attention: Query not run'
    @page.should have_text 'You have an error in your SQL syntax'
  end

  it 'should show query results' do
    @page.query_form.set 'SELECT * FROM exp_channels'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have_text 'Query FormQuery Results' # How Capybara sees the breadcrumb
    results.should have_text 'SELECT * FROM exp_channels'
    results.should have_text 'Total Results: 2'
    results.should have_no_text 'No rows returned'

    results.should have(0).pages
    results.should have(2).rows
    results.table.should have_text 'channel_id'
    results.table.should have_text 'site_id'
    results.table.should have_text 'channel_name'
    results.table.should have_text 'News'
    results.table.should have_text 'Information Pages'
  end

  it 'should sort query results by columns' do
    @page.query_form.set 'SELECT * FROM exp_channels'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.sort_links[0].click # Sort by channel_id descending
    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '2'
    results.table.find('tbody tr:nth-child(2) td:nth-child(1)').should have_text '1'
  end

  it 'should search query results' do
    @page.query_form.set 'select * from exp_channel_titles'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have(0).pages
    results.should have(10).rows

    results.search_field.set 'the'
    results.search_btn.click

    no_php_js_errors
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(2).rows
    results.table.find('tbody tr:nth-child(2) td:nth-child(7)').should have_text 'About the Label'

    # Make sure we can still sort and maintain search results
    results.sort_links[0].click
    no_php_js_errors
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(2).rows
    # This should be in the next row down now
    results.table.find('tbody tr:nth-child(1) td:nth-child(7)').should have_text 'About the Label'
  end

  it 'should paginate query results' do
    # Generate random data that will paginate
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    @page.query_form.set 'select * from exp_cp_log'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
    click_link "Next"

    no_php_js_errors
    results.should have(7).rows
    results.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  end

  it 'should paginate sorted query results' do
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    @page.query_form.set 'select * from exp_cp_log'
    @page.submit

    no_php_js_errors
    results = QueryResults.new
    results.sort_links[0].click
    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '32'

    no_php_js_errors
    click_link "Next"

    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '7'
  end

  def show_status
    status = []
    $db.query('SHOW STATUS').each(:as => :array) do |row|
      status << row[0]
    end
    clear_db_result

    return status
  end

  # SHOW queries sorting, paging and searching are handled by the Table library
  it 'should search SHOW query results' do
    @page.click_link 'SHOW STATUS'

    no_php_js_errors
    results = QueryResults.new
    results.should have(6).pages
    results.should have(25).rows

    results.search_field.set 'alter'
    results.search_btn.click

    status = show_status
    searched = status.sort.grep(/alter/)

    no_php_js_errors
    results.should have_text 'Search Results we found '+searched.count.to_s+' results for "alter"'
    results.search_field.value.should eq 'alter'
    results.should have(0).pages
    results.should have(searched.count).rows
    results.first_column.map {|source| source.text}.should == searched

    # Make sure we can still sort and maintain search results
    results.sort_links[0].click
    results.should have_text 'Search Results we found '+searched.count.to_s+' results for "alter"'
    results.search_field.value.should eq 'alter'
    results.should have(0).pages
    results.should have(searched.count).rows
    results.first_column.map {|source| source.text}.should == searched.reverse
  end

  it 'should paginate SHOW query results' do
    # Generate random data that will paginate
    @page.click_link 'SHOW STATUS'

    status = show_status

    no_php_js_errors
    results = QueryResults.new
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ['First', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort[0..24]
    click_link "Next"

    no_php_js_errors
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ['First', 'Previous', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort[25..49]
  end

  it 'should paginate sorted SHOW query results' do
    @page.click_link 'SHOW STATUS'

    status = show_status

    no_php_js_errors
    results = QueryResults.new
    results.sort_links[0].click
    no_php_js_errors

    results.pages.map {|name| name.text}.should == ['First', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort.reverse[0..24]

    no_php_js_errors
    click_link "Next"

    results.pages.map {|name| name.text}.should == ['First', 'Previous', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort.reverse[25..49]
  end

  it 'should show no results when there are no results' do
    @page.query_form.set 'select * from exp_channels where channel_id = 1000'
    @page.submit

    @page.should have_text 'Total Results: 0'
    @page.should have_text 'No rows returned'
  end

  it 'should show the number of affected rows on write queries' do
    @page.query_form.set 'UPDATE exp_channel_titles SET title = "Kevin" WHERE title = "Josh"'
    @page.submit

    @page.should have_text 'Affected Rows: 1'
    @page.should have_no_text 'Total Results: 0'
    @page.should have_text 'No rows returned'
  end
end

