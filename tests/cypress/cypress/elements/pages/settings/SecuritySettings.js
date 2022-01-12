import ControlPanel from '../ControlPanel'

class SecuritySettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'cp_session_type': 'input[type!=hidden][name=cp_session_type]',
        'website_session_type': 'input[type!=hidden][name=website_session_type]',
        'cookie_domain': 'input[type!=hidden][name=cookie_domain]',
        'cookie_path': 'input[type!=hidden][name=cookie_path]',
        'cookie_prefix': 'input[type!=hidden][name=cookie_prefix]',
        'cookie_httponly': 'input[name=cookie_httponly]', //visible => false
        'cookie_httponly_toggle': '[data-toggle-for=cookie_httponly]',
        'cookie_secure': 'input[name=cookie_secure]', //visible => false
        'cookie_secure_toggle': '[data-toggle-for=cookie_secure]',
        'allow_username_change': 'input[name=allow_username_change]', //visible => false
        'allow_username_change_toggle': '[data-toggle-for=allow_username_change]',
        'un_min_len': 'input[type!=hidden][name=un_min_len]',
        'allow_multi_logins': 'input[name=allow_multi_logins]', //visible => false
        'allow_multi_logins_toggle': '[data-toggle-for=allow_multi_logins]',
        'require_ip_for_login': 'input[name=require_ip_for_login]', //visible => false
        'require_ip_for_login_toggle': '[data-toggle-for=require_ip_for_login]',
        'password_lockout': 'input[name=password_lockout]', //visible => false
        'password_lockout_toggle': '[data-toggle-for=password_lockout]',
        'password_lockout_interval': 'input[name=password_lockout_interval]',
        'password_security_policy': 'input[name=password_security_policy]', //visible => false
        'require_secure_passwords_toggle': '[data-toggle-for=require_secure_passwords]',
        'pw_min_len': 'input[type!=hidden][name=pw_min_len]',
        'allow_dictionary_pw': 'input[name=allow_dictionary_pw]', //visible => false
        'allow_dictionary_pw_toggle': '[data-toggle-for=allow_dictionary_pw]',
        'name_of_dictionary_file': 'input[type!=hidden][name=name_of_dictionary_file]',
        'deny_duplicate_data': 'input[name=deny_duplicate_data]', //visible => false
        'deny_duplicate_data_toggle': '[data-toggle-for=deny_duplicate_data]',
        'require_ip_for_posting': 'input[name=require_ip_for_posting]', //visible => false
        'require_ip_for_posting_toggle': '[data-toggle-for=require_ip_for_posting]',
        'xss_clean_uploads': 'input[name=xss_clean_uploads]', //visible => false
        'xss_clean_uploads_toggle': '[data-toggle-for=xss_clean_uploads]',
        'redirect_submitted_links': 'input[name=redirect_submitted_links]', //visible => false
        'redirect_submitted_links_toggle': '[data-toggle-for=redirect_submitted_links]',
        'force_interstitial': 'input[name=force_redirect]', //visible => false
        'force_interstitial_toggle': '[data-toggle-for=force_redirect]', //visible => false


      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar h2:contains("Security & Privacy")').next('a').click()
  }
}
export default SecuritySettings;
