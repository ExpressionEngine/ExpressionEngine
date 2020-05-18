import ControlPanel from '../ControlPanel'

class CaptchaSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'require_captcha_toggle': '[data-toggle-for=require_captcha]',
        'require_captcha': 'input[name=require_captcha]', //visible => false
        'captcha_font_toggle': '[data-toggle-for=captcha_font]',
        'captcha_font': 'input[name=captcha_font]', //visible => false
        'captcha_rand_toggle': '[data-toggle-for=captcha_rand]',
        'captcha_rand': 'input[name=captcha_rand]', //visible => false
        'captcha_require_members_toggle': '[data-toggle-for=captcha_require_members]',
        'captcha_require_members': 'input[name=captcha_require_members]', //visible => false
        'captcha_url': 'input[type!=hidden][name=captcha_url]',
        'captcha_path': 'input[type!=hidden][name=captcha_path]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("CAPTCHA")').click()
  }
}
export default CaptchaSettings;