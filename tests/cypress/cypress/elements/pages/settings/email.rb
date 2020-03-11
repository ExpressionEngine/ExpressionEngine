class EmailSettings < ControlPanelPage

  element :webmaster_email, 'input[name=webmaster_email]'
  element :webmaster_name, 'input[name=webmaster_name]'
  element :email_charset, 'input[name=email_charset]'
  elements :mail_protocol, 'input[name=mail_protocol]'
  elements :email_newline, 'input[name=email_newline]'
  element :smtp_server, 'input[name=smtp_server]'
  element :smtp_port, 'input[name=smtp_port]'
  element :smtp_username, 'input[name=smtp_username]'
  element :smtp_password, 'input[name=smtp_password]'
  elements :email_smtp_crypto, 'input[name=email_smtp_crypto]'
  elements :mail_format, 'input[name=mail_format]'
  element :word_wrap, 'input[name=word_wrap]', :visible => false
  element :word_wrap_toggle, 'a[data-toggle-for=word_wrap]'

  load
    settings_btn.click
    click_link 'Outgoing Email'
  }
}
