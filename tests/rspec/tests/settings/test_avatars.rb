require './bootstrap.rb'

feature 'Avatar Settings' do

  before(:each) do
    cp_session
    @page = AvatarSettings.new
    @page.load
    no_php_js_errors

    @upload_path = File.expand_path('../../images')
  end

  it 'shows the Avatar Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    @page.avatar_url.value.should == ee_config(item: 'avatar_url')
    @page.avatar_path.value.should == ee_config(item: 'avatar_path')
    @page.avatar_max_width.value.should == ee_config(item: 'avatar_max_width')
    @page.avatar_max_height.value.should == ee_config(item: 'avatar_max_height')
    @page.avatar_max_kb.value.should == ee_config(item: 'avatar_max_kb')
  end

  it 'should validate the form' do
    @page.avatar_path.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    @page.should have_text $invalid_path

    # AJAX validation
    @page.load
    @page.avatar_path.set 'sdfsdfsd'
    @page.avatar_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.avatar_path, $invalid_path)
    should_have_form_errors(@page)

    @page.avatar_path.set @upload_path
    @page.avatar_path.trigger 'blur'
    @page.wait_for_error_message_count(0)

    @page.avatar_path.set '/'
    @page.avatar_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.avatar_path, $not_writable)
    should_have_form_errors(@page)

    @page.avatar_max_width.set 'dfsd'
    @page.avatar_max_width.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.avatar_max_width, $integer_error)
    should_have_form_errors(@page)

    @page.avatar_max_height.set 'dsfsd'
    @page.avatar_max_height.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.avatar_max_height, $integer_error)
    should_have_form_errors(@page)

    @page.avatar_max_kb.set 'sdfsdfsd'
    @page.avatar_max_kb.trigger 'blur'
    @page.wait_for_error_message_count(4)
    should_have_error_text(@page.avatar_max_kb, $integer_error)
    should_have_form_errors(@page)

    # Fix everything
    @page.avatar_path.set @upload_path
    @page.avatar_path.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_no_error_text(@page.avatar_path)
    should_have_form_errors(@page)

    @page.avatar_max_width.set '100'
    @page.avatar_max_width.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.avatar_max_width)
    should_have_form_errors(@page)

    @page.avatar_max_height.set '100'
    @page.avatar_max_height.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.avatar_max_height)
    should_have_form_errors(@page)

    @page.avatar_max_kb.set '100'
    @page.avatar_max_kb.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.avatar_max_kb)
    should_have_no_form_errors(@page)
  end

  it 'should reject XSS' do
    @page.avatar_url.set $xss_vector
    @page.avatar_url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.avatar_url, $xss_error)
    should_have_form_errors(@page)

    @page.avatar_path.set $xss_vector
    @page.avatar_path.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.avatar_url, $xss_error)
    should_have_error_text(@page.avatar_path, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.avatar_url.set 'http://hello'
    @page.avatar_path.set @upload_path
    @page.avatar_max_width.set '100'
    @page.avatar_max_height.set '101'
    @page.avatar_max_kb.set '102'
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.avatar_url.value.should == 'http://hello'
    @page.avatar_path.value.should == @upload_path
    @page.avatar_max_width.value.should == '100'
    @page.avatar_max_height.value.should == '101'
    @page.avatar_max_kb.value.should == '102'
  end
end
