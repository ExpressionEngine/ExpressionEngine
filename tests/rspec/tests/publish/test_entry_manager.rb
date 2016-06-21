require './bootstrap.rb'

feature 'Entry Manager' do
  before :each do
    cp_session
    @page = EntryManager.new
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

  it 'deletes a single entry' do
    @page.should have(10).entry_rows

    @page.entry_checkboxes[0].click
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_for_modal_submit_button
    @page.modal_submit_button.click

    @page.should have(9).entry_rows
    @page.alert.text.should include 'The following entries were removed'
  end

  it 'deletes all entries' do
    @page.should have(10).entry_rows
    @page.entry_checkboxes.each(&:click)

    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_for_modal_submit_button
    @page.modal_submit_button.click

    @page.should have(1).entry_rows
    @page.entry_rows[0].text.should include 'No Entries found.'
    @page.alert.text.should include 'The following entries were removed'
  end

  it 'deletes 100 entries' do
    @page.create_entries(100)
    @page.load(perpage: 100)

    # ... leaves the last item out of the range
    @page.entry_checkboxes.each(&:click)
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_for_modal_submit_button
    @page.modal_submit_button.click

    @page.should have(10).entry_rows
    @page.alert.text.should include 'The following entries were removed'
    @page.alert.text.should include 'and 96 others...'
  end
end
