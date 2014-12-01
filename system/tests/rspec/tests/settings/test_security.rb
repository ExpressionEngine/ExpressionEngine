require './bootstrap.rb'

feature 'Security & Privacy Settings' do

  before(:each) do
    cp_session
    @page = SecuritySettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Security & Privacy Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    cookie_httponly = ee_config(item: 'cookie_httponly')
    cookie_secure = ee_config(item: 'cookie_secure')
    allow_username_change = ee_config(item: 'allow_username_change')
    allow_multi_logins = ee_config(item: 'allow_multi_logins')
    require_ip_for_login = ee_config(item: 'require_ip_for_login')
    password_lockout = ee_config(item: 'password_lockout')
    require_secure_passwords = ee_config(item: 'require_secure_passwords')
    allow_dictionary_pw = ee_config(item: 'allow_dictionary_pw')
    deny_duplicate_data = ee_config(item: 'deny_duplicate_data')
    require_ip_for_posting = ee_config(item: 'require_ip_for_posting')
    xss_clean_uploads = ee_config(item: 'xss_clean_uploads')

    @page.cp_session_type.value.should == ee_config(item: 'cp_session_type')
    @page.website_session_type.value.should == ee_config(item: 'website_session_type')
    @page.cookie_domain.value.should == ee_config(item: 'cookie_domain')
    @page.cookie_path.value.should == ee_config(item: 'cookie_path')
    @page.cookie_prefix.value.should == ee_config(item: 'cookie_prefix')
    @page.cookie_httponly_y.checked?.should == (cookie_httponly == 'y')
    @page.cookie_httponly_n.checked?.should == (cookie_httponly == 'n')
    @page.cookie_secure_y.checked?.should == (cookie_secure == 'y')
    @page.cookie_secure_n.checked?.should == (cookie_secure == 'n')
    @page.allow_username_change_y.checked?.should == (allow_username_change == 'y')
    @page.allow_username_change_n.checked?.should == (allow_username_change == 'n')
    @page.un_min_len.value.should == ee_config(item: 'un_min_len')
    @page.allow_multi_logins_y.checked?.should == (allow_multi_logins == 'y')
    @page.allow_multi_logins_n.checked?.should == (allow_multi_logins == 'n')
    @page.require_ip_for_login_y.checked?.should == (require_ip_for_login == 'y')
    @page.require_ip_for_login_n.checked?.should == (require_ip_for_login == 'n')
    @page.password_lockout_y.checked?.should == (password_lockout == 'y')
    @page.password_lockout_n.checked?.should == (password_lockout == 'n')
    @page.password_lockout_interval.value.should == ee_config(item: 'password_lockout_interval')
    @page.require_secure_passwords_y.checked?.should == (require_secure_passwords == 'y')
    @page.require_secure_passwords_n.checked?.should == (require_secure_passwords == 'n')
    @page.pw_min_len.value.should == ee_config(item: 'pw_min_len')
    @page.allow_dictionary_pw_y.checked?.should == (allow_dictionary_pw == 'y')
    @page.allow_dictionary_pw_n.checked?.should == (allow_dictionary_pw == 'n')
    @page.name_of_dictionary_file.value.should == ee_config(item: 'name_of_dictionary_file')
    @page.deny_duplicate_data_y.checked?.should == (deny_duplicate_data == 'y')
    @page.deny_duplicate_data_n.checked?.should == (deny_duplicate_data == 'n')
    @page.require_ip_for_posting_y.checked?.should == (require_ip_for_posting == 'y')
    @page.require_ip_for_posting_n.checked?.should == (require_ip_for_posting == 'n')
    @page.xss_clean_uploads_y.checked?.should == (xss_clean_uploads == 'y')
    @page.xss_clean_uploads_n.checked?.should == (xss_clean_uploads == 'n')
  end

  it 'should validate the form' do
    integer_error = 'This field must contain an integer.'

    @page.un_min_len.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.un_min_len, integer_error)

    # AJAX validation
    @page.load
    @page.un_min_len.set 'sdfsdfsd'
    @page.un_min_len.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.un_min_len, integer_error)
    should_have_form_errors(@page)

    @page.password_lockout_interval.set 'sdfsdfsd'
    @page.password_lockout_interval.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.password_lockout_interval, integer_error)
    should_have_form_errors(@page)

    @page.pw_min_len.set 'sdfsdfsd'
    @page.pw_min_len.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_error_text(@page.pw_min_len, integer_error)
    should_have_form_errors(@page)

    # Fix everything
    @page.un_min_len.set '5'
    @page.un_min_len.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.un_min_len)
    should_have_form_errors(@page)

    @page.password_lockout_interval.set '15'
    @page.password_lockout_interval.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.password_lockout_interval)
    should_have_form_errors(@page)

    @page.pw_min_len.set '8'
    @page.pw_min_len.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.pw_min_len)
    should_have_no_form_errors(@page)
  end

  it 'should save and load the settings' do
    @page.cp_session_type.select 'Session ID only'
    @page.website_session_type.select 'Session ID only'
    @page.cookie_domain.set '.yourdomain.com'
    @page.cookie_path.set 'blog'
    @page.cookie_httponly_n.click
    # Changing cookie_secure will boot us out of the CP
    @page.allow_username_change_n.click
    @page.un_min_len.set '5'
    @page.allow_multi_logins_n.click
    @page.require_ip_for_login_n.click
    @page.password_lockout_n.click
    @page.password_lockout_interval.set '15'
    @page.require_secure_passwords_y.click
    @page.pw_min_len.set '8'
    @page.allow_dictionary_pw_n.click
    @page.name_of_dictionary_file.set 'http://dictionary'
    @page.deny_duplicate_data_n.click
    @page.require_ip_for_posting_n.click
    @page.xss_clean_uploads_n.click
    @page.submit

    # Since we changed session settings, login again
    cp_session
    @page.load

    @page.should have_text 'Preferences updated'
    @page.cp_session_type.value.should == 's'
    @page.website_session_type.value.should == 's'
    @page.cookie_domain.value.should == '.yourdomain.com'
    @page.cookie_path.value.should == 'blog'
    @page.cookie_httponly_n.checked?.should == true
    @page.allow_username_change_n.checked?.should == true
    @page.un_min_len.value.should == '5'
    @page.allow_multi_logins_n.checked?.should == true
    @page.require_ip_for_login_n.checked?.should == true
    @page.password_lockout_n.checked?.should == true
    @page.password_lockout_interval.value.should == '15'
    @page.require_secure_passwords_y.checked?.should == true
    @page.pw_min_len.value.should == '8'
    @page.allow_dictionary_pw_n.checked?.should == true
    @page.name_of_dictionary_file.value.should == 'http://dictionary'
    @page.deny_duplicate_data_n.checked?.should == true
    @page.require_ip_for_posting_n.checked?.should == true
    @page.xss_clean_uploads_n.checked?.should == true
  end
end