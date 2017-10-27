require './bootstrap.rb'

feature 'Outgoing Email Settings' do
  let(:field_required)  { 'This field is required.' }
  let(:email_invalid)   { 'This field must contain a valid email address.' }
  let(:server_required) { 'This field is required for SMTP.' }
  let(:natural_number)  { 'This field must contain a number greater than zero.' }

  before :each do
    skip "waiting on es6 solution for Capybara" do
    end
    cp_session
    @page = EmailSettings.new
    @page.load
    no_php_js_errors
  end

  context 'when validating with page loads' do

    it 'should load current email settings into form fields' do
      @page.webmaster_email.value.should == ee_config(item: 'webmaster_email')
      @page.webmaster_name.value.should == ee_config(item: 'webmaster_name')
      @page.email_charset.value.should == ee_config(item: 'email_charset')
      @page.mail_protocol.value.should == ee_config(item: 'mail_protocol')
      @page.email_newline.value.sub(/\\n/, "\n").should == ee_config(item: 'email_newline')
      @page.mail_format.value.should == ee_config(item: 'mail_format')

      # SMTP fields are hidden unless SMTP is selected

      word_wrap = ee_config(item: 'word_wrap')
      @page.word_wrap_y.checked?.should == (word_wrap == 'y')
      @page.word_wrap_n.checked?.should == (word_wrap == 'n')
    end

    it 'validates SMTP server when that is the selected protocol' do
      @page.mail_protocol.select 'SMTP'
      @page.smtp_server.set ''
      @page.submit

      no_php_js_errors
      should_have_form_errors(@page)
      @page.should have_text 'Attention: Settings not saved'
      should_have_error_text(@page.smtp_server, server_required)
    end

    it 'should save and load the settings' do
      @page.webmaster_email.set 'test@test.com'
      @page.webmaster_name.set 'Trey Anastasio'
      @page.email_charset.set 'somecharset'
      @page.mail_protocol.select 'SMTP'
      @page.smtp_server.set 'google.com'
      @page.smtp_port.set '587'
      @page.smtp_username.set 'username'
      @page.smtp_password.set 'password'
      @page.mail_format.select 'HTML'
      @page.word_wrap_n.click
      @page.submit

      @page.should have_text 'Preferences updated'
      @page.webmaster_email.value.should == 'test@test.com'
      @page.webmaster_name.value.should == 'Trey Anastasio'
      @page.email_charset.value.should == 'somecharset'
      @page.mail_protocol.value.should == 'smtp'
      @page.smtp_server.value.should == 'google.com'
      @page.smtp_port.value.should == '587'
      @page.smtp_username.value.should == 'username'
      @page.smtp_password.value.should == 'password'
      @page.mail_format.value.should == 'html'
      @page.word_wrap_y.checked?.should == false
      @page.word_wrap_n.checked?.should == true
    end
  end

  context 'when validating using Ajax' do
    it 'validates mail protocol' do
      @page.mail_protocol.select 'SMTP'

      @page.wait_until_smtp_server_visible
      @page.wait_until_smtp_port_visible
      @page.wait_until_smtp_username_visible
      @page.wait_until_smtp_password_visible
      @page.wait_until_email_smtp_crypto_visible

      @page.smtp_server.set ''
      @page.smtp_server.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(@page.smtp_server, server_required)
    end

    it 'validates webmaster email when using an empty string' do
      @page.webmaster_email.set ''
      @page.webmaster_email.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(@page.webmaster_email, field_required)

      @page.webmaster_email.set 'test@test.com'
      @page.webmaster_email.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_error_text(@page.webmaster_email)
    end

    it 'validates webmaster name using a xss vector' do
      @page.webmaster_name.set $xss_vector
      @page.webmaster_name.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(@page.webmaster_name, $xss_error)

      @page.webmaster_name.set 'Trey Anastasio'
      @page.webmaster_name.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_error_text(@page.webmaster_name)
    end

    it 'validates webmaster email when using nonsense' do
      @page.webmaster_email.set 'dfsfdsf'
      @page.webmaster_email.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_error_text(@page.webmaster_email, email_invalid)

      @page.webmaster_email.set 'test@test.com'
      @page.webmaster_email.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_error_text(@page.webmaster_email)
    end

    it 'validates mail protocol when using PHP mail' do
      @page.mail_protocol.select 'PHP mail'
      @page.mail_protocol.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_form_errors(@page)
    end

    it 'validates SMTP port' do
      @page.mail_protocol.select 'SMTP'

      @page.wait_until_smtp_server_visible
      @page.wait_until_smtp_port_visible
      @page.wait_until_smtp_username_visible
      @page.wait_until_smtp_password_visible
      @page.wait_until_email_smtp_crypto_visible

      @page.smtp_port.set 'abc'
      @page.smtp_port.trigger 'blur'
      @page.wait_for_error_message_count(1)
      should_have_error_text(@page.smtp_port, natural_number)

      @page.smtp_port.set '587'
      @page.smtp_port.trigger 'blur'
      @page.wait_for_error_message_count(0)
      should_have_no_form_errors(@page)
      should_have_no_error_text(@page.smtp_port)
    end
  end
end
