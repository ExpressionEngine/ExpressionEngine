require './bootstrap.rb'

context('SQL Manager', () => {

  def get_tables
    tables = []
    $db.query('SHOW TABLES').each(:as => :array) do |row|
      tables << row[0]
    }
    clear_db_result

    tables
  }

  beforeEach(function() {
    cy.auth();
    page = SqlManager.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the SQL Manager', () => {
    page.get('wrap').contains('SQL Manager'
    page.get('wrap').contains('Total Records'
    page.get('wrap').contains('Database Tables'
    page.should have_search_field
    page.should have_search_btn
    #page.should have_op_select
    #page.should have_op_submit
  }

  it('should list tables present in the install', () => {
    tables = get_tables

    page.tables.map {|source| source.text}.should == tables
    page.should have(tables.count).tables
  }

  it('should sort the table', () => {
    page.table.find('th.highlight').text.should eq 'Table Name'
    page.sort_links[0].click()

    tables = get_tables

    page.tables.map {|source| source.text}.should == tables.reverse
    page.should have(tables.count).tables
    page.table.find('th.highlight').text.should eq 'Table Name'
  }

  it('should search the table names', () => {
    tables = get_tables

    page.search_field.clear().type('access'
    page.search_btn.click()

    page.get('wrap').contains('Search Results we found 3 results for "access"'

    page.tables.map {|source| source.text}.should == tables.grep(/access/)
  }

  it('should sort search results', () => {
    tables = get_tables

    page.search_field.clear().type('access'
    page.search_btn.click()

    page.sort_links[0].click()

    page.get('wrap').contains('Search Results we found 3 results for "access"'

    page.tables.map {|source| source.text}.should == tables.grep(/access/).reverse
  }

  it('should validate the table operations submission', () => {
    page.select_all.click()
    page.wait_until_op_submit_visible
    page.op_submit.click()

    page.get('alert').contains('You must select an action to perform on the selected tables.'
  }

  it('should repair the tables and sort and search the results', () => {
    page.select_all.click()
    page.wait_until_op_select_visible
    page.op_select.select 'Repair'
    page.op_submit.click()

    cy.hasNoErrors()
    page.get('wrap').contains('Repair Table Results'

    tables = get_tables

    page.tables.map {|source| source.text}.should == tables

    // Go ahead and test sorting
    page.sort_links[0].click()
    cy.hasNoErrors()
    page.tables.map {|source| source.text}.should == tables.reverse

    // And search
    page.search_field.clear().type('category'
    page.search_btn.click()

    page.tables.map {|source| source.text}.should == ['exp_category_field_data', 'exp_category_fields', 'exp_category_groups', 'exp_category_posts'].reverse
  }

  it('should optimize the tables and sort and search the results', () => {
    page.select_all.click()
    page.wait_until_op_select_visible
    page.op_select.select 'Optimize'
    page.op_submit.click()

    cy.hasNoErrors()
    page.get('wrap').contains('Optimized Table Results'

    tables = get_tables

    // This checks that the list of tables (@pages.table) _includes_ the list of
    // tables we retrieved earlier. The `*` in front of `tables` is a splat
    // operator that takes the array and returns the values as agruments
    // https://endofline.wordpress.com/2011/01/21/the-strange-ruby-splat/

    page.tables.map(&:text).should include(*tables)

    // Go ahead and test sorting
    page.sort_links[0].click()
    cy.hasNoErrors()
    page.tables.map(&:text).should include(*tables.reverse)

    // And search
    page.search_field.clear().type('category'
    page.search_btn.click()

    page.tables.map(&:text).should include(*['exp_category_field_data', 'exp_category_fields', 'exp_category_groups', 'exp_category_posts'].reverse)
  }

  it('should allow viewing of table contents', () => {
    page.manage_links[0].click()

    results = QueryResults.new
    cy.hasNoErrors()
    results.contains('SQL Managerexp_actions Table' // How Capybara sees the breadcrumb
    results.contains('exp_actions Table'
    results.should have(21).rows

    // Make sure breadcrumb info persists in base URL
    results.sort_links[0].click()
    results.contains('SQL Managerexp_actions Table'
  }
}
