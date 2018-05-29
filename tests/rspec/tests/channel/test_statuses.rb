require './bootstrap.rb'

feature 'Statuses' do

  before(:each) do
    skip "Needs fleshing out for new channel manager" do
    end
    cp_session
    @page = Statuses.new
    @page.load_view_for_status_group(1)
    no_php_js_errors

    @statuses = get_statuses_for_group(1)
  end

  def get_statuses_for_group(group)
    statuses = []
    $db.query('SELECT status FROM exp_statuses WHERE group_id = '+group.to_s+' ORDER BY status_order ASC').each(:as => :array) do |row|
      statuses << row[0]
    end
    clear_db_result

    return statuses
  end

  it 'should list the statuses' do
    @page.status_names.map {|source| source.text}.should == @statuses
    @page.should have(@statuses.count).status_names

    # Also, this table should not be sortable; since you can reorder
    # the statuses, having an option to sort them is confusing, you
    # don't know if sorting them changes the order in the DB or not
    @page.should have_no_sort_col
  end

# This test for some reason doesn't work in versions of jQuery UI that set
# a fixed placeholder height on table rows, Google suggests these headless
# browser drivers don't work with Sortable very well
#  it 'should drag and drop statuses to reorder' do
#    # Drag the drag handle to the third row
#    @page.statuses[0].find('td:first-child').drag_to @page.statuses[2]
#
#    # Make our statuses array match what the table SHOULD be, and
#    # check the table for it
#    moved_status = @statuses.delete_at(0)
#    @page.status_names.map {|source| source.text}.should == @statuses.insert(2, moved_status)
#
#    # Reload the page and make sure it stuck
#    @page.load_view_for_status_group(1)
#    @page.status_names.map {|source| source.text}.should == @statuses
#  end

  it 'should delete a status' do
    @page.statuses[2].find('input[type="checkbox"]').set true
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Status: ' + @statuses[2]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include 'Statuses removed'
    @page.alert.text.should include '1 statuses were removed.'
    @page.status_names.count.should == @statuses.count - 1

    @statuses.delete_at(2)
    @page.status_names.map {|source| source.text}.should == @statuses
    @page.should have(@statuses.count).status_names
  end

  it 'should bulk delete statuses, except the default statuses' do
    @page.select_all.click
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    # Minus two for default statuses
    if @statuses.count - 2 <= 5
      for status in @statuses
        if (['open', 'closed'].include? status.downcase) == false
          @page.modal.should have_text 'Status: ' + status
        end
      end
    end

    @page.modal.should have_no_text 'Status: open'
    @page.modal.should have_no_text 'Status: closed'

    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include 'Statuses removed'
    @page.alert.text.should include (@statuses.count - 2).to_s + ' statuses were removed.'
    @page.status_names.count.should == 2
    @page.status_names.map {|source| source.text}.should == ['open', 'closed']
  end
end
