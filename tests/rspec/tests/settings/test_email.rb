require './bootstrap.rb'

feature 'Outgoing Email Settings' do

  before(:each) do
    cp_session
    @page = EmailSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Outgoing Email Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current email settings into form fields' do
    @page.webmaster_email.value.should == ee_config(item: 'webmaster_email')
    @page.webmaster_name.value.should == ee_config(item: 'webmaster_name')
    @page.email_charset.value.should == ee_config(item: 'email_charset')
    @page.mail_protocol.value.should == ee_config(item: 'mail_protocol')
    @page.smtp_server.value.should == ee_config(item: 'smtp_server')
    @page.smtp_username.value.should == ee_config(item: 'smtp_username')
    @page.smtp_password.value.should == ee_config(item: 'smtp_password')
    @page.mail_format.value.should == ee_config(item: 'mail_format')

    word_wrap = ee_config(item: 'word_wrap')
    @page.word_wrap_y.checked?.should == (word_wrap == 'y')
    @page.word_wrap_n.checked?.should == (word_wrap == 'n')
  end

  it 'should validate the form' do
    field_required = "This field is required."
    email_invalid = 'This field must contain a valid email address.'
    server_required = 'This field is required for SMTP.'

    @page.mail_protocol.select 'SMTP'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.smtp_server, server_required)

    # AJAX validation
    @page.load
    @page.webmaster_email.set ''
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.webmaster_email, field_required)

    @page.mail_protocol.select 'SMTP'
    @page.smtp_server.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    should_have_error_text(@page.smtp_server, server_required)

    @page.webmaster_name.set $xss_vector
    @page.webmaster_name.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_form_errors(@page)
    should_have_error_text(@page.webmaster_name, $xss_error)

    @page.webmaster_name.set 'Trey Anastasio'
    @page.webmaster_name.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_no_error_text(@page.webmaster_name)

    @page.webmaster_email.set 'test@test.com'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.webmaster_email)

    @page.webmaster_email.set 'dfsfdsf'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.webmaster_email, email_invalid)

    @page.webmaster_email.set 'test@test.com'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_no_error_text(@page.webmaster_email)

    @page.mail_protocol.select 'PHP Mail'
    @page.smtp_server.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.smtp_server)
  end

  it 'should save and load the settings' do
    @page.webmaster_email.set 'test@test.com'
    @page.webmaster_name.set 'Trey Anastasio'
    @page.email_charset.set 'somecharset'
    @page.mail_protocol.select 'SMTP'
    @page.smtp_server.set 'google.com'
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
    @page.smtp_username.value.should == 'username'
    @page.smtp_password.value.should == 'password'
    @page.mail_format.value.should == 'html'
    @page.word_wrap_y.checked?.should == false
    @page.word_wrap_n.checked?.should == true
  end
end