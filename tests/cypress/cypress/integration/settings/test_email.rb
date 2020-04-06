require './bootstrap.rb'

context('Outgoing Email Settings', () => {
  let(:field_required)  { 'This field is required.' }
  let(:email_invalid)   { 'This field must contain a valid email address.' }
  let(:server_required) { 'This field is required for SMTP.' }
  let(:natural_number)  { 'This field must contain a number greater than zero.' }

  beforeEach(function(){
    cy.auth();
    page = EmailSettings.new
    page.load()
    cy.hasNoErrors()
  }

  context('when validating with page loads', () => {

    it('should load current email settings into form fields', () => {
      email_newline = eeConfig({item: 'email_newline')

      page.webmaster_email.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'webmaster_email')
      page.webmaster_name.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'webmaster_name')
      page.email_charset.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'email_charset')
      page.mail_protocol.has_checked_radio(eeConfig({item: 'mail_protocol')).should == true
      page.email_newline.has_checked_radio(email_newline.sub(/\n/, "\\n")).should == true
      page.mail_format.has_checked_radio(eeConfig({item: 'mail_format')).should == true

      // SMTP fields are hidden unless SMTP is selected

      word_wrap = eeConfig({item: 'word_wrap')
      page.word_wrap.invoke('val').then((val) => { expect(val).to.be.equal(word_wrap
    }

    it('validates SMTP server when that is the selected protocol', () => {
      page.mail_protocol.choose_radio_option('smtp')
      page.submit

      cy.hasNoErrors()
      page.hasErrors()
//should_have_form_errors(page)
      page.get('wrap').contains('Attention: Settings not saved'
      page.hasError(page.smtp_server, server_required)
    }

    it('should save and load the settings', () => {
      page.webmaster_email.clear().type('test@test.com'
      page.webmaster_name.clear().type('Trey Anastasio'
      page.email_charset.clear().type('somecharset'
      page.mail_protocol.choose_radio_option('smtp')
      page.smtp_server.clear().type('google.com'
      page.smtp_port.clear().type('587'
      page.smtp_username.clear().type('username'
      page.smtp_password.clear().type('password'
      page.mail_format.choose_radio_option('html')
      page.word_wrap_toggle.click()
      page.submit

      page.get('wrap').contains('Preferences updated'
      page.webmaster_email.invoke('val').then((val) => { expect(val).to.be.equal('test@test.com'
      page.webmaster_name.invoke('val').then((val) => { expect(val).to.be.equal('Trey Anastasio'
      page.email_charset.invoke('val').then((val) => { expect(val).to.be.equal('somecharset'
      page.mail_protocol.has_checked_radio('smtp').should == true
      page.smtp_server.invoke('val').then((val) => { expect(val).to.be.equal('google.com'
      page.smtp_port.invoke('val').then((val) => { expect(val).to.be.equal('587'
      page.smtp_username.invoke('val').then((val) => { expect(val).to.be.equal('username'
      page.smtp_password.invoke('val').then((val) => { expect(val).to.be.equal('password'
      page.mail_format.has_checked_radio('html').should == true
      page.word_wrap.invoke('val').then((val) => { expect(val).to.be.equal('n'
    }
  }

  context('when validating using Ajax', () => {
    it('validates mail protocol', () => {
      page.mail_protocol.choose_radio_option('smtp')

      page.wait_until_smtp_server_visible
      page.wait_until_smtp_port_visible
      page.wait_until_smtp_username_visible
      page.wait_until_smtp_password_visible
      page.wait_until_email_smtp_crypto_visible

      page.smtp_server.clear().type(''
      page.smtp_server.blur()
      page.wait_for_error_message_count(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.smtp_server, server_required)
    }

    it('validates webmaster email when using an empty string', () => {
      page.webmaster_email.clear().type(''
      page.webmaster_email.blur()
      page.wait_for_error_message_count(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.webmaster_email, field_required)

      page.webmaster_email.clear().type('test@test.com'
      page.webmaster_email.blur()
      page.wait_for_error_message_count(0)
      should_have_no_error_text(page.webmaster_email)
    }

    it('validates webmaster name using a xss vector', () => {
      page.webmaster_name.clear().type(page.messages.xss_vector)
      page.webmaster_name.blur()
      page.wait_for_error_message_count(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.webmaster_name, page.messages.xss_error)

      page.webmaster_name.clear().type('Trey Anastasio'
      page.webmaster_name.blur()
      page.wait_for_error_message_count(0)
      should_have_no_error_text(page.webmaster_name)
    }

    it('validates webmaster email when using nonsense', () => {
      page.webmaster_email.clear().type('dfsfdsf'
      page.webmaster_email.blur()
      page.wait_for_error_message_count(1)
      page.hasError(page.webmaster_email, email_invalid)

      page.webmaster_email.clear().type('test@test.com'
      page.webmaster_email.blur()
      page.wait_for_error_message_count(0)
      should_have_no_error_text(page.webmaster_email)
    }

    it('validates mail protocol when using PHP mail', () => {
      page.mail_protocol.choose_radio_option('mail')
      page.mail_protocol[0].blur()
      page.wait_for_error_message_count(0)
      should_have_no_form_errors(page)
    }

    it('validates SMTP port', () => {
      page.mail_protocol.choose_radio_option('smtp')

      page.wait_until_smtp_server_visible
      page.wait_until_smtp_port_visible
      page.wait_until_smtp_username_visible
      page.wait_until_smtp_password_visible
      page.wait_until_email_smtp_crypto_visible

      page.smtp_port.clear().type('abc'
      page.smtp_port.blur()
      page.wait_for_error_message_count(1)
      page.hasError(page.smtp_port, natural_number)

      page.smtp_port.clear().type('587'
      page.smtp_port.blur()
      page.wait_for_error_message_count(0)
      should_have_no_form_errors(page)
      should_have_no_error_text(page.smtp_port)
    }
  }
}
