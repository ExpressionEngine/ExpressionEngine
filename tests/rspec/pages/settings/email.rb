class EmailSettings < ControlPanelPage

	element :webmaster_email, 'input[name=webmaster_email]'
	element :webmaster_name, 'input[name=webmaster_name]'
	element :email_charset, 'input[name=email_charset]'
	element :mail_protocol, 'select[name=mail_protocol]'
	element :smtp_server, 'input[name=smtp_server]'
	element :smtp_username, 'input[name=smtp_username]'
	element :smtp_password, 'input[name=smtp_password]'
	element :mail_format, 'select[name=mail_format]'
	element :word_wrap_y, 'input[name=word_wrap][value=y]'
	element :word_wrap_n, 'input[name=word_wrap][value=n]'

	def load
		settings_btn.click
		click_link 'Outgoing Email'
	end
end