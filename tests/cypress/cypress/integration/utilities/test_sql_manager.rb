require './bootstrap.rb'

feature 'SQL Manager' do

  def get_tables
    tables = []
    $db.query('SHOW TABLES').each(:as => :array) do |row|
      tables << row[0]
    end
    clear_db_result

    tables
  end

  before(:each) do
    cp_session
    @page = SqlManager.new
    @page.load
    no_php_js_errors
  end

  it 'shows the SQL Manager' do
    @page.should have_text 'SQL Manager'
    @page.should have_text 'Total Records'
    @page.should have_text 'Database Tables'
    @page.should have_search_field
    @page.should have_search_btn
    #@page.should have_op_select
    #@page.should have_op_submit
  end

  it 'should list tables present in the install' do
    tables = get_tables

    @page.tables.map {|source| source.text}.should == tables
    @page.should have(tables.count).tables
  end

  it 'should sort the table' do
    @page.table.find('th.highlight').text.should eq 'Table Name'
    @page.sort_links[0].click

    tables = get_tables

    @page.tables.map {|source| source.text}.should == tables.reverse
    @page.should have(tables.count).tables
    @page.table.find('th.highlight').text.should eq 'Table Name'
  end

  it 'should search the table names' do
    tables = get_tables

    @page.search_field.set 'access'
    @page.search_btn.click

    @page.should have_text 'Search Results we found 3 results for "access"'

    @page.tables.map {|source| source.text}.should == tables.grep(/access/)
  end

  it 'should sort search results' do
    tables = get_tables

    @page.search_field.set 'access'
    @page.search_btn.click

    @page.sort_links[0].click

    @page.should have_text 'Search Results we found 3 results for "access"'

    @page.tables.map {|source| source.text}.should == tables.grep(/access/).reverse
  end

  it 'should validate the table operations submission' do
    @page.select_all.click
    @page.wait_until_op_submit_visible
    @page.op_submit.click

    @page.alert.should have_text 'You must select an action to perform on the selected tables.'
  end

  it 'should repair the tables and sort and search the results' do
    @page.select_all.click
    @page.wait_until_op_select_visible
    @page.op_select.select 'Repair'
    @page.op_submit.click

    no_php_js_errors
    @page.should have_text 'Repair Table Results'

    tables = get_tables

    @page.tables.map {|source| source.text}.should == tables

    # Go ahead and test sorting
    @page.sort_links[0].click
    no_php_js_errors
    @page.tables.map {|source| source.text}.should == tables.reverse

    # And search
    @page.search_field.set 'category'
    @page.search_btn.click

    @page.tables.map {|source| source.text}.should == ['exp_category_field_data', 'exp_category_fields', 'exp_category_groups', 'exp_category_posts'].reverse
  end

  it 'should optimize the tables and sort and search the results' do
    @page.select_all.click
    @page.wait_until_op_select_visible
    @page.op_select.select 'Optimize'
    @page.op_submit.click

    no_php_js_errors
    @page.should have_text 'Optimized Table Results'

    tables = get_tables

    # This checks that the list of tables (@pages.table) _includes_ the list of
    # tables we retrieved earlier. The `*` in front of `tables` is a splat
    # operator that takes the array and returns the values as agruments
    # https://endofline.wordpress.com/2011/01/21/the-strange-ruby-splat/

    @page.tables.map(&:text).should include(*tables)

    # Go ahead and test sorting
    @page.sort_links[0].click
    no_php_js_errors
    @page.tables.map(&:text).should include(*tables.reverse)

    # And search
    @page.search_field.set 'category'
    @page.search_btn.click

    @page.tables.map(&:text).should include(*['exp_category_field_data', 'exp_category_fields', 'exp_category_groups', 'exp_category_posts'].reverse)
  end

  it 'should allow viewing of table contents' do
    @page.manage_links[0].click

    results = QueryResults.new
    no_php_js_errors
    results.should have_text 'SQL Managerexp_actions Table' # How Capybara sees the breadcrumb
    results.should have_text 'exp_actions Table'
    results.should have(21).rows

    # Make sure breadcrumb info persists in base URL
    results.sort_links[0].click
    results.should have_text 'SQL Managerexp_actions Table'
  end
end
