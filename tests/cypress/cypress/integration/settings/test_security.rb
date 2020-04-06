require './bootstrap.rb'

context('Security & Privacy Settings', () => {

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
    cookie_httponly = eeConfig({item: 'cookie_httponly')
    cookie_secure = eeConfig({item: 'cookie_secure')
    allow_username_change = eeConfig({item: 'allow_username_change')
    allow_multi_logins = eeConfig({item: 'allow_multi_logins')
    require_ip_for_login = eeConfig({item: 'require_ip_for_login')
    password_lockout = eeConfig({item: 'password_lockout')
    require_secure_passwords = eeConfig({item: 'require_secure_passwords')
    allow_dictionary_pw = eeConfig({item: 'allow_dictionary_pw')
    deny_duplicate_data = eeConfig({item: 'deny_duplicate_data')
    require_ip_for_posting = eeConfig({item: 'require_ip_for_posting')
    xss_clean_uploads = eeConfig({item: 'xss_clean_uploads')
    redirect_submitted_links = eeConfig({item: 'redirect_submitted_links')
    force_redirect = eeConfig({item: 'force_redirect')
    if force_redirect == ''
      force_redirect = 'n'
    }

    page.cp_session_type.has_checked_radio(eeConfig({item: 'cp_session_type')).should == true
    page.website_session_type.has_checked_radio(eeConfig({item: 'website_session_type')).should == true
    page.cookie_domain.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'cookie_domain')
    page.cookie_path.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'cookie_path')
    page.cookie_prefix.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'cookie_prefix')
    page.cookie_httponly.invoke('val').then((val) => { expect(val).to.be.equal(cookie_httponly
    page.cookie_secure.invoke('val').then((val) => { expect(val).to.be.equal(cookie_secure
    page.allow_username_change.invoke('val').then((val) => { expect(val).to.be.equal(allow_username_change
    page.un_min_len.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'un_min_len')
    page.allow_multi_logins.invoke('val').then((val) => { expect(val).to.be.equal(allow_multi_logins
    page.require_ip_for_login.invoke('val').then((val) => { expect(val).to.be.equal(require_ip_for_login
    page.password_lockout.invoke('val').then((val) => { expect(val).to.be.equal(password_lockout
    page.password_lockout_interval.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'password_lockout_interval')
    page.require_secure_passwords.invoke('val').then((val) => { expect(val).to.be.equal(require_secure_passwords
    page.pw_min_len.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'pw_min_len')
    page.allow_dictionary_pw.invoke('val').then((val) => { expect(val).to.be.equal(allow_dictionary_pw
    page.name_of_dictionary_file.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'name_of_dictionary_file')
    page.deny_duplicate_data.invoke('val').then((val) => { expect(val).to.be.equal(deny_duplicate_data
    page.require_ip_for_posting.invoke('val').then((val) => { expect(val).to.be.equal(require_ip_for_posting
    page.xss_clean_uploads.invoke('val').then((val) => { expect(val).to.be.equal(xss_clean_uploads
    page.redirect_submitted_links.invoke('val').then((val) => { expect(val).to.be.equal(redirect_submitted_links
    page.force_interstitial.invoke('val').then((val) => { expect(val).to.be.equal(force_redirect
  }

  it('should validate the form', () => {
    integer_error = 'This field must contain an integer.'

    page.un_min_len.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.un_min_len, integer_error)

    // AJAX validation
    page.load()
    page.un_min_len.clear().type('sdfsdfsd'
    page.un_min_len.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.un_min_len, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.password_lockout_interval.clear().type('sdfsdfsd'
    page.password_lockout_interval.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.password_lockout_interval, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.pw_min_len.clear().type('sdfsdfsd'
    page.pw_min_len.blur()
    // page.wait_for_error_message_count(3)
    page.hasError(page.pw_min_len, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.un_min_len.clear().type('5'
    page.un_min_len.blur()
    //page.wait_for_error_message_count(2)
    should_have_no_error_text(page.un_min_len)
    page.hasErrors()
//should_have_form_errors(page)

    page.password_lockout_interval.clear().type('15'
    page.password_lockout_interval.blur()
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.password_lockout_interval)
    page.hasErrors()
//should_have_form_errors(page)

    page.pw_min_len.clear().type('8'
    page.pw_min_len.blur()
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
    page.cookie_domain.clear().type('.yourdomain.com'
    page.cookie_path.clear().type('blog'
    page.cookie_httponly_toggle.click()
    // Changing cookie_secure will boot us out of the CP
    page.allow_username_change_toggle.click()
    page.un_min_len.clear().type('5'
    page.allow_multi_logins_toggle.click()
    page.require_ip_for_login_toggle.click()
    page.password_lockout_toggle.click()
    page.password_lockout_interval.clear().type('15'
    page.require_secure_passwords_toggle.click()
    page.pw_min_len.clear().type('8'
    page.allow_dictionary_pw_toggle.click()
    page.name_of_dictionary_file.clear().type('http://dictionary'
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

    page.get('wrap').contains('Preferences updated'
    page.cp_session_type.has_checked_radio('s').should == true
    page.website_session_type.has_checked_radio('s').should == true
    page.cookie_domain.invoke('val').then((val) => { expect(val).to.be.equal('.yourdomain.com'
    page.cookie_path.invoke('val').then((val) => { expect(val).to.be.equal('blog'
    page.cookie_httponly.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.allow_username_change.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.un_min_len.invoke('val').then((val) => { expect(val).to.be.equal('5'
    page.allow_multi_logins.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.require_ip_for_login.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.password_lockout.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.password_lockout_interval.invoke('val').then((val) => { expect(val).to.be.equal('15'
    page.require_secure_passwords.invoke('val').then((val) => { expect(val).to.be.equal('y'
    page.pw_min_len.invoke('val').then((val) => { expect(val).to.be.equal('8'
    page.allow_dictionary_pw.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.name_of_dictionary_file.invoke('val').then((val) => { expect(val).to.be.equal('http://dictionary'
    page.deny_duplicate_data.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.require_ip_for_posting.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.xss_clean_uploads.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.redirect_submitted_links.invoke('val').then((val) => { expect(val).to.be.equal('y'
    page.force_interstitial.invoke('val').then((val) => { expect(val).to.be.equal('y'
  }
}
