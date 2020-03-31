require './bootstrap.rb'

context('CAPTCHA Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = CaptchaSettings.new
    page.load()
    cy.hasNoErrors()

    @upload_path = File.expand_path('../../images')
  }

  it('shows the CAPTCHA Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    require_captcha = eeConfig({item: 'require_captcha')
    captcha_font = eeConfig({item: 'captcha_font')
    captcha_rand = eeConfig({item: 'captcha_rand')
    captcha_require_members = eeConfig({item: 'captcha_require_members')

    page.require_captcha.value.should == require_captcha
    page.captcha_font.value.should == captcha_font
    page.captcha_rand.value.should == captcha_rand
    page.captcha_require_members.value.should == captcha_require_members
    page.captcha_url.value.should == eeConfig({item: 'captcha_url')
    page.captcha_path.value.should == eeConfig({item: 'captcha_path')
  }

  it('should validate the form', () => {
    page.captcha_path.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    should_have_error_text(page.captcha_path, $invalid_path)

    // AJAX validation
    page.load()
    page.captcha_path.clear().type('sdfsdfsd'
    page.captcha_path.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.captcha_path, $invalid_path)
    should_have_form_errors(page)

    page.captcha_path.set @upload_path
    page.captcha_path.trigger 'blur'
    page.wait_for_error_message_count(0)

    page.captcha_path.clear().type('/'
    page.captcha_path.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.captcha_path, $not_writable)
    should_have_form_errors(page)
  }

  it('should reject XSS', () => {
    page.captcha_url.set $xss_vector
    page.captcha_url.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.captcha_url, $xss_error)
    should_have_form_errors(page)

    page.captcha_path.set $xss_vector
    page.captcha_path.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.captcha_url, $xss_error)
    should_have_error_text(page.captcha_path, $xss_error)
    should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    require_captcha = eeConfig({item: 'require_captcha')
    captcha_font = eeConfig({item: 'captcha_font')
    captcha_rand = eeConfig({item: 'captcha_rand')
    captcha_require_members = eeConfig({item: 'captcha_require_members')

    page.require_captcha_toggle.click()
    page.captcha_font_toggle.click()
    page.captcha_rand_toggle.click()
    page.captcha_require_members_toggle.click()
    page.captcha_url.clear().type('http://hello'
    page.captcha_path.set @upload_path
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.require_captcha.value.should_not == require_captcha
    page.captcha_font.value.should_not == captcha_font
    page.captcha_rand.value.should_not == captcha_rand
    page.captcha_require_members.value.should_not == captcha_require_members
    page.captcha_url.value.should == 'http://hello'
    page.captcha_path.value.should == @upload_path
  }
}
