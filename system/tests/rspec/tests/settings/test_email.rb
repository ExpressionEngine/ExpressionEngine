require './bootstrap.rb'

feature 'Outgoing Email Settings' do

  before(:each) do
    cp_session
    @page = EmailSettings.new
    @page.load
    no_php_js_errors

    @webmaster_email = ee_config(item: 'webmaster_email')
    @webmaster_name = ee_config(item: 'webmaster_name')
    @email_charset = ee_config(item: 'email_charset')
    @mail_protocol = ee_config(item: 'mail_protocol')
    @smtp_server = ee_config(item: 'smtp_server')
    @smtp_username = ee_config(item: 'smtp_username')
    @smtp_password = ee_config(item: 'smtp_password')
    @mail_format = ee_config(item: 'mail_format')
    @word_wrap = ee_config(item: 'word_wrap')
  end

  it 'shows the Outgoing Email Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current email settings into form fields' do
    @page.webmaster_email.value.should == @webmaster_email
    @page.webmaster_name.value.should == @webmaster_name
    @page.email_charset.value.should == @email_charset
    @page.mail_protocol.value.should == @mail_protocol
    @page.smtp_server.value.should == @smtp_server
    @page.smtp_username.value.should == @smtp_username
    @page.smtp_password.value.should == @smtp_password
    @page.mail_format.value.should == @mail_format
    @page.word_wrap_y.checked?.should == (@word_wrap == 'y')
    @page.word_wrap_n.checked?.should == (@word_wrap == 'n')
  end

  it 'should validate the form' do
    email_required = 'The "Address" field is required.'
    email_invalid = 'The "Address" field must contain a valid email address.'
    server_required = 'The "Server address" field is required for SMTP.'

    @page.mail_protocol.select 'SMTP'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    @page.should have_text server_required

    # AJAX validation
    @page.load
    @page.webmaster_email.set ''
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text email_required

    @page.mail_protocol.select 'SMTP'
    @page.smtp_server.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    @page.should have_text server_required

    @page.webmaster_email.set 'test@test.com'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)

    @page.webmaster_email.set 'dfsfdsf'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(2)
    @page.should have_text email_invalid

    @page.webmaster_email.set 'test@test.com'
    @page.webmaster_email.trigger 'blur'
    @page.wait_for_error_message_count(1)

    @page.mail_protocol.select 'PHP Mail'
    @page.smtp_server.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    @page.should have_no_text email_required
    @page.should have_no_text email_invalid
    @page.should have_no_text server_required
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

    @page.should have_text 'Preferences Updated'
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