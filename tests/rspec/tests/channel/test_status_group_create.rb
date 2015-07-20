require './bootstrap.rb'

feature 'Status Group Create/Edit' do

  before(:each) do
    cp_session
    @page = StatusGroupCreate.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Status Group Create/Edit page' do
    @page.all_there?.should == true
    @page.should have_text 'Create Status Group'
  end

  it 'should validate regular fields' do
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Status group not saved'
    should_have_error_text(@page.group_name, $required_error)

    # AJAX validation
    # Required name
    @page.load
    @page.group_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.group_name, $required_error)
    should_have_form_errors(@page)

    @page.group_name.set 'Test'
    @page.group_name.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.group_name)
    should_have_no_form_errors(@page)

    # Duplicate group name
    @page.group_name.set 'Default'
    @page.group_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.group_name, 'A status group already exists with the same name.')
    should_have_form_errors(@page)

    @page.group_name.set 'Test'
    @page.group_name.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.group_name)
    should_have_no_form_errors(@page)
  end

  it 'should reject XSS' do
    @page.group_name.set $xss_vector
    @page.group_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.group_name, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should save a new status group and load edit form' do
    @page.group_name.set 'Test'
    @page.submit
    no_php_js_errors

    @page.should have_text 'Status group saved'

    @page.load_edit_for_status_group(2)

    @page.should have_text 'Edit Status Group'
    should_have_no_form_errors(@page)

    @page.group_name.value.should == 'Test'
  end

  it 'should edit an existing status group' do
    @page.load_edit_for_status_group(1)
    @page.should have_text 'Edit Status Group'
    no_php_js_errors

    @page.group_name.value.should == 'Default'

    @page.group_name.set 'Test'
    @page.submit

    no_php_js_errors
    @page.should have_text 'Status group saved'

    @page.load_edit_for_status_group(1)
    @page.should have_text 'Edit Status Group'
    @page.group_name.value.should == 'Test'
  end
end
