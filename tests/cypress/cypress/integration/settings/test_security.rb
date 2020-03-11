require './bootstrap.rb'

feature 'Security & Privacy Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = SecuritySettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Security & Privacy Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
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
    redirect_submitted_links = ee_config(item: 'redirect_submitted_links')
    force_redirect = ee_config(item: 'force_redirect')
    if force_redirect == ''
      force_redirect = 'n'
    }

    page.cp_session_type.has_checked_radio(ee_config(item: 'cp_session_type')).should == true
    page.website_session_type.has_checked_radio(ee_config(item: 'website_session_type')).should == true
    page.cookie_domain.value.should == ee_config(item: 'cookie_domain')
    page.cookie_path.value.should == ee_config(item: 'cookie_path')
    page.cookie_prefix.value.should == ee_config(item: 'cookie_prefix')
    page.cookie_httponly.value.should == cookie_httponly
    page.cookie_secure.value.should == cookie_secure
    page.allow_username_change.value.should == allow_username_change
    page.un_min_len.value.should == ee_config(item: 'un_min_len')
    page.allow_multi_logins.value.should == allow_multi_logins
    page.require_ip_for_login.value.should == require_ip_for_login
    page.password_lockout.value.should == password_lockout
    page.password_lockout_interval.value.should == ee_config(item: 'password_lockout_interval')
    page.require_secure_passwords.value.should == require_secure_passwords
    page.pw_min_len.value.should == ee_config(item: 'pw_min_len')
    page.allow_dictionary_pw.value.should == allow_dictionary_pw
    page.name_of_dictionary_file.value.should == ee_config(item: 'name_of_dictionary_file')
    page.deny_duplicate_data.value.should == deny_duplicate_data
    page.require_ip_for_posting.value.should == require_ip_for_posting
    page.xss_clean_uploads.value.should == xss_clean_uploads
    page.redirect_submitted_links.value.should == redirect_submitted_links
    page.force_interstitial.value.should == force_redirect
  }

  it('should validate the form', () => {
    integer_error = 'This field must contain an integer.'

    page.un_min_len.set 'sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.should have_text 'Attention: Settings not saved'
    should_have_error_text(page.un_min_len, integer_error)

    // AJAX validation
    page.load()
    page.un_min_len.set 'sdfsdfsd'
    page.un_min_len.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.un_min_len, integer_error)
    should_have_form_errors(page)

    page.password_lockout_interval.set 'sdfsdfsd'
    page.password_lockout_interval.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.password_lockout_interval, integer_error)
    should_have_form_errors(page)

    page.pw_min_len.set 'sdfsdfsd'
    page.pw_min_len.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.pw_min_len, integer_error)
    should_have_form_errors(page)

    // Fix everything
    page.un_min_len.set '5'
    page.un_min_len.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.un_min_len)
    should_have_form_errors(page)

    page.password_lockout_interval.set '15'
    page.password_lockout_interval.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.password_lockout_interval)
    should_have_form_errors(page)

    page.pw_min_len.set '8'
    page.pw_min_len.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.pw_min_len)
    should_have_no_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.cp_session_type.choose_radio_option('cs')
    page.submit

    cy.auth();
    page.load()

    page.cp_session_type.choose_radio_option('s')
    page.website_session_type.choose_radio_option('s')
    page.cookie_domain.set '.yourdomain.com'
    page.cookie_path.set 'blog'
    page.cookie_httponly_toggle.click()
    // Changing cookie_secure will boot us out of the CP
    page.allow_username_change_toggle.click()
    page.un_min_len.set '5'
    page.allow_multi_logins_toggle.click()
    page.require_ip_for_login_toggle.click()
    page.password_lockout_toggle.click()
    page.password_lockout_interval.set '15'
    page.require_secure_passwords_toggle.click()
    page.pw_min_len.set '8'
    page.allow_dictionary_pw_toggle.click()
    page.name_of_dictionary_file.set 'http://dictionary'
    page.deny_duplicate_data_toggle.click()
    page.require_ip_for_posting_toggle.click()
    page.xss_clean_uploads_toggle.click()
    page.redirect_submitted_links_toggle.click()

    page.force_interstitial_toggle.visible?.should == true
    page.force_interstitial_toggle.click()
    page.submit

    // Since we changed session settings, login again
    cy.auth();
    page.load()

    page.should have_text 'Preferences updated'
    page.cp_session_type.has_checked_radio('s').should == true
    page.website_session_type.has_checked_radio('s').should == true
    page.cookie_domain.value.should == '.yourdomain.com'
    page.cookie_path.value.should == 'blog'
    page.cookie_httponly.value.should == 'n'
    page.allow_username_change.value.should == 'n'
    page.un_min_len.value.should == '5'
    page.allow_multi_logins.value.should == 'n'
    page.require_ip_for_login.value.should == 'n'
    page.password_lockout.value.should == 'n'
    page.password_lockout_interval.value.should == '15'
    page.require_secure_passwords.value.should == 'y'
    page.pw_min_len.value.should == '8'
    page.allow_dictionary_pw.value.should == 'n'
    page.name_of_dictionary_file.value.should == 'http://dictionary'
    page.deny_duplicate_data.value.should == 'n'
    page.require_ip_for_posting.value.should == 'n'
    page.xss_clean_uploads.value.should == 'n'
    page.redirect_submitted_links.value.should == 'y'
    page.force_interstitial.value.should == 'y'
  }
}
