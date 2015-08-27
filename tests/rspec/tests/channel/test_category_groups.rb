require './bootstrap.rb'

feature 'Category Groups' do

  before(:each) do
    cp_session
    @page = CategoryGroups.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Category Groups page' do
    @page.all_there?.should == true
    @page.should have_text 'Group Name'
  end

  def get_group_names
    groups = []
    $db.query('SELECT group_name FROM exp_category_groups ORDER BY group_id ASC').each(:as => :array) do |row|
      groups << row[0]
    end
    clear_db_result

    return groups
  end

  def get_group_names_with_cat_count
    groups = []
    $db.query('SELECT group_name, count(exp_categories.cat_id)
      FROM exp_category_groups
      LEFT JOIN exp_categories ON exp_categories.group_id = exp_category_groups.group_id
      GROUP BY group_name
      ORDER BY exp_category_groups.group_id ASC').each(:as => :array) do |row|
      groups << row[0] + ' ('+row[1].to_s+')'
    end
    clear_db_result

    return groups
  end

  it 'should list the category groups' do
    groups = get_group_names_with_cat_count

    @page.group_names.map {|source| source.text}.should == groups
    @page.should have(groups.count).group_names
  end

  it 'should sort the list of category groups' do
    groups = get_group_names_with_cat_count
    @page.group_names.map {|source| source.text}.should == groups
    @page.sort_col.text.should eq 'ID#'

    @page.sort_links[0].click
    no_php_js_errors

    @page.group_names.map {|source| source.text}.should == groups.reverse
    @page.should have(groups.count).group_names
    @page.sort_col.text.should eq 'ID#'

    @page.sort_links[1].click

    # Sort alphabetically
    @page.group_names.map {|source| source.text}.should == groups.sort
    @page.should have(groups.count).group_names
    @page.sort_col.text.should eq 'Group Name'

    @page.sort_links[1].click

    # Sort reverse alphabetically
    @page.group_names.map {|source| source.text}.should == groups.sort.reverse
    @page.should have(groups.count).group_names
    @page.sort_col.text.should eq 'Group Name'
  end

  it 'should delete a category group' do
    groups = get_group_names

    # Also set a sort state to make sure it's maintained
    @page.sort_links[1].click
    no_php_js_errors
    @page.sort_col.text.should eq 'Group Name'

    @page.category_groups[2].find('input[type="checkbox"]').set true
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Category Group: ' + groups.sort[1]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Category groups removed'
    @page.alert.text.should include '1 category groups were removed.'
    @page.group_names.count.should == groups.count - 1

    # Check that it maintained sort state
    @page.sort_col.text.should eq 'Group Name'
  end

  it 'should bulk delete category groups' do
    groups = get_group_names
    @page.select_all.click
	@page.wait_until_bulk_action_visible
    @page.bulk_action.select 'Remove'
    @page.action_submit_button.click
    @page.wait_until_modal_visible

    if groups.count <= 5
      for group_name in groups
        @page.modal.should have_text 'Category Group: ' + group_name
      end
    end

    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.alert[:class].should include 'success'
    @page.alert.text.should include 'Category groups removed'
    @page.alert.text.should include groups.count.to_s + ' category groups were removed.'
    @page.group_names.count.should == 0

    @page.table.should have_text 'No Category Groups'
    @page.table.should have_text 'CREATE CATEGORY GROUP'
  end
end