require './bootstrap.rb'

feature 'Entry Manager' do
  before :each do
    cp_session
    @page = Edit.new
    @page.load
    no_php_js_errors
  end

  it 'creates entries' do
    row = $db.query('SELECT count(entry_id) AS count FROM exp_channel_titles').first
    row['count'].should == 10
    @page.should have(10).entry_rows

    @page.create_entries
    @page.load

    row = $db.query('SELECT count(entry_id) AS count FROM exp_channel_titles').first
    row['count'].should == 20
    @page.should have(20).entry_rows
  end

  it 'loads a page with 100 entries' do
    @page.create_entries(100)
    @page.load(perpage: 100)

    row = $db.query('SELECT count(entry_id) AS count FROM exp_channel_titles').first
    row['count'].should == 110
    @page.should have(100).entry_rows
  end
end
