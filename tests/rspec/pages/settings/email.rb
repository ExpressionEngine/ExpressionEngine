class EmailSettings < ControlPanelPage

  element :webmaster_email, 'input[name=webmaster_email]'
  element :webmaster_name, 'input[name=webmaster_name]'
  element :email_charset, 'input[name=email_charset]'
  element :mail_protocol, 'select[name=mail_protocol]'
  element :email_newline, 'select[name=email_newline]'
  element :smtp_server, 'input[name=smtp_server]'
  element :smtp_port, 'input[name=smtp_port]'
  element :smtp_username, 'input[name=smtp_username]'
  element :smtp_password, 'input[name=smtp_password]'
  element :email_smtp_crypto, 'select[name=email_smtp_crypto]'
  element :mail_format, 'select[name=mail_format]'
  element :word_wrap, 'input[name=word_wrap]', :visible => false
  element :word_wrap_toggle, 'a[data-toggle-for=word_wrap]'

  def load
    settings_btn.click
    click_link 'Outgoing Email'
  end
end
