class SecuritySettings < ControlPanelPage

  elements :cp_session_type, 'input[name=cp_session_type]'
  elements :website_session_type, 'input[name=website_session_type]'
  element :cookie_domain, 'input[name=cookie_domain]'
  element :cookie_path, 'input[name=cookie_path]'
  element :cookie_prefix, 'input[name=cookie_prefix]'
  element :cookie_httponly, 'input[name=cookie_httponly]', :visible => false
  element :cookie_httponly_toggle, 'a[data-toggle-for=cookie_httponly]'
  element :cookie_secure, 'input[name=cookie_secure]', :visible => false
  element :cookie_secure_toggle, 'a[data-toggle-for=cookie_secure]'
  element :allow_username_change, 'input[name=allow_username_change]', :visible => false
  element :allow_username_change_toggle, 'a[data-toggle-for=allow_username_change]'
  element :un_min_len, 'input[name=un_min_len]'
  element :allow_multi_logins, 'input[name=allow_multi_logins]', :visible => false
  element :allow_multi_logins_toggle, 'a[data-toggle-for=allow_multi_logins]'
  element :require_ip_for_login, 'input[name=require_ip_for_login]', :visible => false
  element :require_ip_for_login_toggle, 'a[data-toggle-for=require_ip_for_login]'
  element :password_lockout, 'input[name=password_lockout]', :visible => false
  element :password_lockout_toggle, 'a[data-toggle-for=password_lockout]'
  element :password_lockout_interval, 'input[name=password_lockout_interval]'
  element :require_secure_passwords, 'input[name=require_secure_passwords]', :visible => false
  element :require_secure_passwords_toggle, 'a[data-toggle-for=require_secure_passwords]'
  element :pw_min_len, 'input[name=pw_min_len]'
  element :allow_dictionary_pw, 'input[name=allow_dictionary_pw]', :visible => false
  element :allow_dictionary_pw_toggle, 'a[data-toggle-for=allow_dictionary_pw]'
  element :name_of_dictionary_file, 'input[name=name_of_dictionary_file]'
  element :deny_duplicate_data, 'input[name=deny_duplicate_data]', :visible => false
  element :deny_duplicate_data_toggle, 'a[data-toggle-for=deny_duplicate_data]'
  element :require_ip_for_posting, 'input[name=require_ip_for_posting]', :visible => false
  element :require_ip_for_posting_toggle, 'a[data-toggle-for=require_ip_for_posting]'
  element :xss_clean_uploads, 'input[name=xss_clean_uploads]', :visible => false
  element :xss_clean_uploads_toggle, 'a[data-toggle-for=xss_clean_uploads]'
  element :redirect_submitted_links, 'input[name=redirect_submitted_links]', :visible => false
  element :redirect_submitted_links_toggle, 'a[data-toggle-for=redirect_submitted_links]'
  element :force_interstitial, 'input[name=force_redirect]', :visible => false
  element :force_interstitial_toggle, 'a[data-toggle-for=force_redirect]', :visible => false

  load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Security & Privacy'
    }
  }
}
