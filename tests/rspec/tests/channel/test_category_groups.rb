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
    @page.should have_text 'Category Manager'
  end

  def get_group_names
    groups = []
    $db.query('SELECT group_name FROM exp_category_groups ORDER BY group_name ASC').each(:as => :array) do |row|
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
      GROUP BY group_name, exp_category_groups.group_id
      ORDER BY exp_category_groups.group_name ASC').each(:as => :array) do |row|
      groups << row[0]
    end
    clear_db_result

    return groups
  end

  it 'should list the category groups' do
    groups = get_group_names_with_cat_count

    @page.group_names.map {|source| source.text}.should == groups
    @page.should have(groups.count).group_names
  end

  it 'should delete a category group' do
    groups = get_group_names

    @page.category_groups[0].find('li.remove a').click
    @page.wait_until_modal_visible
    @page.modal.should have_text 'Category Group: ' + groups[0]
    @page.modal_submit_button.click
    no_php_js_errors

    @page.should have_alert
    @page.should have_alert_success
    @page.alert.text.should include 'Category group removed'
    @page.group_names.count.should == groups.count - 1
  end
end
