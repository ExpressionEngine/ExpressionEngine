require './bootstrap.rb'

feature 'Status Groups manager' do

  before(:each) do
    cp_session
    @page = StatusGroups.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Status Groups manager page' do
    @page.all_there?.should == true
    @page.should have_text 'Status Groups'
  end

  def get_status_groups
    status_groups = []
    $db.query('SELECT group_name FROM exp_status_groups ORDER BY group_id ASC').each(:as => :array) do |row|
      status_groups << row[0]
    end
    clear_db_result

    return status_groups
  end

  it 'should list the status groups' do
    status_groups = get_status_groups

    @page.status_group_titles.map {|source| source.text}.should == status_groups
    @page.should have(status_groups.count).status_group_titles
  end

  def create_dummy_groups
    status_group_create = StatusGroupCreate.new
    status_group_create.load
    status_group_create.group_name.set 'Test'
    status_group_create.submit
    status_group_create.should have_text 'Status Group Created'

    status_group_create.load
    status_group_create.group_name.set 'Another test'
    status_group_create.submit
    status_group_create.should have_text 'Status Group Created'
  end

  it 'should sort the list of status groups' do
    create_dummy_groups
    status_groups = get_status_groups

    @page.load
    @page.sort_col.text.should eq 'ID#'
    @page.status_group_titles.map {|source| source.text}.should == status_groups
    @page.should have(status_groups.count).status_group_titles

    @page.sort_links[1].click
    no_php_js_errors

    # Sort alphabetically
    @page.sort_col.text.should eq 'Group Name'
    @page.status_group_titles.map {|source| source.text}.should == status_groups.sort
    @page.should have(status_groups.count).status_group_titles

    @page.sort_links[1].click
    no_php_js_errors

    # Sort reverse alphabetically
    @page.sort_col.text.should eq 'Group Name'
    @page.status_group_titles.map {|source| source.text}.should == status_groups.sort.reverse
    @page.should have(status_groups.count).status_group_titles
  end

  it 'should delete a status group' do
    create_dummy_groups
    status_groups = get_status_groups

    # Also set a sort state to make sure it's maintained
    @page.load
    @page.sort_links[1].click
    no_php_js_errors
    @page.sort_col.text.should eq 'Group Name'

    @page.status_groups[2].find('input[type="checkbox"]').set true
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Status Group: ' + status_groups.sort[2]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Status groups removed'
    @page.alert.text.should include '1 status groups were removed.'
    @page.status_group_titles.count.should == status_groups.count - 1

    # Check that it maintained sort state
    @page.sort_col.text.should eq 'Group Name'
    status_groups = get_status_groups
    @page.status_group_titles.map {|source| source.text}.should == status_groups.sort
    @page.should have(status_groups.count).status_group_titles
  end

  it 'should bulk delete status groups, except the default group' do
    create_dummy_groups
    status_groups = get_status_groups

    @page.load
    @page.select_all.click
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    # Minus one for default group
    if status_groups.count - 1 <= 5
      for status_group in status_groups
        if status_group != 'Default'
          @page.modal.should have_text 'Status Group: ' + status_group
        end
      end
    end

    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Status groups removed'
    @page.alert.text.should include (status_groups.count - 1).to_s + ' status groups were removed.'
    @page.status_group_titles.count.should == 1
    @page.status_group_titles.map {|source| source.text}.should == ['Default']
  end
end
