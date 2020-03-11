class CaptchaSettings < ControlPanelPage

  element :require_captcha_toggle, 'a[data-toggle-for=require_captcha]'
  element :require_captcha, 'input[name=require_captcha]', :visible => false
  element :captcha_font_toggle, 'a[data-toggle-for=captcha_font]'
  element :captcha_font, 'input[name=captcha_font]', :visible => false
  element :captcha_rand_toggle, 'a[data-toggle-for=captcha_rand]'
  element :captcha_rand, 'input[name=captcha_rand]', :visible => false
  element :captcha_require_members_toggle, 'a[data-toggle-for=captcha_require_members]'
  element :captcha_require_members, 'input[name=captcha_require_members]', :visible => false
  element :captcha_url, 'input[name=captcha_url]'
  element :captcha_path, 'input[name=captcha_path]'

  load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'CAPTCHA'
    }
  }
}
