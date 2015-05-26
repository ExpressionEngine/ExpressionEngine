class SecuritySettings < ControlPanelPage

	element :cp_session_type, 'select[name=cp_session_type]'
	element :website_session_type, 'select[name=website_session_type]'
	element :cookie_domain, 'input[name=cookie_domain]'
	element :cookie_path, 'input[name=cookie_path]'
	element :cookie_prefix, 'input[name=cookie_prefix]'
	element :cookie_httponly_y, 'input[name=cookie_httponly][value=y]'
	element :cookie_httponly_n, 'input[name=cookie_httponly][value=n]'
	element :cookie_secure_y, 'input[name=cookie_secure][value=y]'
	element :cookie_secure_n, 'input[name=cookie_secure][value=n]'
	element :allow_username_change_y, 'input[name=allow_username_change][value=y]'
	element :allow_username_change_n, 'input[name=allow_username_change][value=n]'
	element :un_min_len, 'input[name=un_min_len]'
	element :allow_multi_logins_y, 'input[name=allow_multi_logins][value=y]'
	element :allow_multi_logins_n, 'input[name=allow_multi_logins][value=n]'
	element :require_ip_for_login_y, 'input[name=require_ip_for_login][value=y]'
	element :require_ip_for_login_n, 'input[name=require_ip_for_login][value=n]'
	element :password_lockout_y, 'input[name=password_lockout][value=y]'
	element :password_lockout_n, 'input[name=password_lockout][value=n]'
	element :password_lockout_interval, 'input[name=password_lockout_interval]'
	element :require_secure_passwords_y, 'input[name=require_secure_passwords][value=y]'
	element :require_secure_passwords_n, 'input[name=require_secure_passwords][value=n]'
	element :pw_min_len, 'input[name=pw_min_len]'
	element :allow_dictionary_pw_y, 'input[name=allow_dictionary_pw][value=y]'
	element :allow_dictionary_pw_n, 'input[name=allow_dictionary_pw][value=n]'
	element :name_of_dictionary_file, 'input[name=name_of_dictionary_file]'
	element :deny_duplicate_data_y, 'input[name=deny_duplicate_data][value=y]'
	element :deny_duplicate_data_n, 'input[name=deny_duplicate_data][value=n]'
	element :require_ip_for_posting_y, 'input[name=require_ip_for_posting][value=y]'
	element :require_ip_for_posting_n, 'input[name=require_ip_for_posting][value=n]'
	element :xss_clean_uploads_y, 'input[name=xss_clean_uploads][value=y]'
	element :xss_clean_uploads_n, 'input[name=xss_clean_uploads][value=n]'

	def load
		settings_btn.click
		within 'div.sidebar' do
			click_link 'Security & Privacy'
		end
	end
end