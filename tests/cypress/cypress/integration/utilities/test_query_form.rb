require './bootstrap.rb'

feature 'Query Form', () => {

  beforeEach(function() {
    cy.auth();
    page = QueryForm.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Query Form', () => {
    page.should have_text 'Query to run'
    page.should have_query_form
  }

  it('should validate the form', () => {
    field_required = 'This field is required.'
    form_error = 'Attention: Query not run'

    // Submit with nothing
    page.submit

    cy.hasNoErrors()
    page.should have_text form_error
    should_have_error_text(page.query_form, field_required)
    should_have_form_errors(page)

    // AJAX Validation
    page.load()
    page.query_form.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.query_form, field_required)

    page.query_form.set 'SELECT'
    page.query_form.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.query_form)
  }

  it('should not allow certain query types', () => {
    not_allowed = 'Query type not allowed'

    page.query_form.set "FLUSH TABLES"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "REPLACE INTO offices(officecode,city) VALUES(8,'San Jose')"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "GRANT ALL ON db1.* TO 'jeffrey'@'localhost'"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "REVOKE INSERT ON *.* FROM 'jeffrey'@'localhost'"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "LOCK TABLES t1 READ"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "UNLOCK TABLES t1 READ"
    page.submit

    cy.hasNoErrors()
    page.should have_text not_allowed

    page.query_form.set "SELECT * FROM exp_channels"
    page.submit

    cy.hasNoErrors()
    page.should have_no_text not_allowed
  }

  it('should show MySQL errors', () => {
    error_text = 'You have an error in your SQL syntax'

    // Invalid query with errors on
    page.query_form.set "SELECT FROM exp_channels"
    page.submit

    cy.hasNoErrors()
    page.should have_text 'Attention: Query not run'
    page.should have_text 'You have an error in your SQL syntax'
  }

  it('should show query results', () => {
    page.query_form.set 'SELECT * FROM exp_channels'
    page.submit

    cy.hasNoErrors()
    results = QueryResults.new
    results.should have_text 'Query FormQuery Results' // How Capybara sees the breadcrumb
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
  }

  it('should sort query results by columns', () => {
    page.query_form.set 'SELECT * FROM exp_channels'
    page.submit

    cy.hasNoErrors()
    results = QueryResults.new
    results.sort_links[0].click() // Sort by channel_id descending
    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '2'
    results.table.find('tbody tr:nth-child(2) td:nth-child(1)').should have_text '1'
  }

  it('should search query results', () => {
    page.query_form.set 'select * from exp_channel_titles'
    page.submit

    cy.hasNoErrors()
    results = QueryResults.new
    results.should have(0).pages
    results.should have(10).rows

    results.search_field.set 'the'
    results.search_btn.click()

    cy.hasNoErrors()
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(2).rows
    results.table.find('tbody tr:nth-child(2) td:nth-child(7)').should have_text 'About the Label'

    // Make sure we can still sort and maintain search results
    results.sort_links[0].click()
    cy.hasNoErrors()
    results.should have_text 'Search Results we found 2 results for "the"'
    results.search_field.value.should eq 'the'
    results.should have(0).pages
    results.should have(2).rows
    // This should be in the next row down now
    results.table.find('tbody tr:nth-child(1) td:nth-child(7)').should have_text 'About the Label'
  }

  it('should paginate query results', () => {
    // Generate random data that will paginate
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    page.query_form.set 'select * from exp_cp_log'
    page.submit

    cy.hasNoErrors()
    results = QueryResults.new
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
    click_link "Next"

    cy.hasNoErrors()
    results.should have(7).rows
    results.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  }

  it('should paginate sorted query results', () => {
    cp_log = CpLog.new
    cp_log.generate_data(count: 30)

    page.query_form.set 'select * from exp_cp_log'
    page.submit

    cy.hasNoErrors()
    results = QueryResults.new
    results.sort_links[0].click()
    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '32'

    cy.hasNoErrors()
    click_link "Next"

    results.table.find('tbody tr:nth-child(1) td:nth-child(1)').should have_text '7'
  }

  def show_status
    status = []
    $db.query('SHOW STATUS').each(:as => :array) do |row|
      status << row[0]
    }
    clear_db_result

    return status
  }

  // SHOW queries sorting, paging and searching are handled by the Table library
  it('should search SHOW query results', () => {
    page.click_link 'SHOW STATUS'

    cy.hasNoErrors()
    results = QueryResults.new
    results.should have(6).pages
    results.should have(25).rows

    results.search_field.set 'alter'
    results.search_btn.click()

    status = show_status
    searched = status.sort.grep(/alter/)

    cy.hasNoErrors()
    results.should have_text 'Search Results we found '+searched.count.to_s+' results for "alter"'
    results.search_field.value.should eq 'alter'
    results.should have(0).pages
    results.should have(searched.count).rows
    results.first_column.map {|source| source.text}.should == searched

    // Make sure we can still sort and maintain search results
    results.sort_links[0].click()
    results.should have_text 'Search Results we found '+searched.count.to_s+' results for "alter"'
    results.search_field.value.should eq 'alter'
    results.should have(0).pages
    results.should have(searched.count).rows
    results.first_column.map {|source| source.text}.should == searched.reverse
  }

  it('should paginate SHOW query results', () => {
    // Generate random data that will paginate
    page.click_link 'SHOW STATUS'

    status = show_status

    cy.hasNoErrors()
    results = QueryResults.new
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ['First', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort[0..24]
    click_link "Next"

    cy.hasNoErrors()
    results.should have(25).rows
    results.pages.map {|name| name.text}.should == ['First', 'Previous', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort[25..49]
  }

  it('should paginate sorted SHOW query results', () => {
    page.click_link 'SHOW STATUS'

    status = show_status

    cy.hasNoErrors()
    results = QueryResults.new
    results.sort_links[0].click()
    cy.hasNoErrors()

    results.pages.map {|name| name.text}.should == ['First', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort.reverse[0..24]

    cy.hasNoErrors()
    click_link "Next"

    results.pages.map {|name| name.text}.should == ['First', 'Previous', '1', '2', '3', 'Next', 'Last']
    results.first_column.map {|source| source.text}.should == status.sort.reverse[25..49]
  }

  it('should show no results when there are no results', () => {
    page.query_form.set 'select * from exp_channels where channel_id = 1000'
    page.submit

    page.should have_text 'Total Results: 0'
    page.should have_text 'No rows returned'
  }

  it('should show the number of affected rows on write queries', () => {
    page.query_form.set 'UPDATE exp_channel_titles SET title = "Kevin" WHERE title = "Josh"'
    page.submit

    page.should have_text 'Affected Rows: 1'
    page.should have_no_text 'Total Results: 0'
    page.should have_text 'No rows returned'
  }
}
