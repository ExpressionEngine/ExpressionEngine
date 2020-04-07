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

    page.require_captcha.invoke('val').then((val) => { expect(val).to.be.equal(require_captcha
    page.captcha_font.invoke('val').then((val) => { expect(val).to.be.equal(captcha_font
    page.captcha_rand.invoke('val').then((val) => { expect(val).to.be.equal(captcha_rand
    page.captcha_require_members.invoke('val').then((val) => { expect(val).to.be.equal(captcha_require_members
    page.captcha_url.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'captcha_url')
    page.captcha_path.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'captcha_path')
  }

  it('should validate the form', () => {
    page.captcha_path.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.captcha_path, page.messages.validation.invalid_path)

    // AJAX validation
    page.load()
    page.captcha_path.clear().type('sdfsdfsd'
    page.captcha_path.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.captcha_path, page.messages.validation.invalid_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.captcha_path.set @upload_path
    page.captcha_path.blur()
    page.wait_for_error_message_count(0)

    page.captcha_path.clear().type('/'
    page.captcha_path.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.captcha_path, $not_writable)
    page.hasErrors()
//should_have_form_errors(page)
  }

  it('should reject XSS', () => {
    page.captcha_url.clear().type(page.messages.xss_vector)
    page.captcha_url.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.captcha_url, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.captcha_path.clear().type(page.messages.xss_vector)
    page.captcha_path.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.captcha_url, page.messages.xss_error)
    page.hasError(page.captcha_path, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
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
    page.captcha_url.invoke('val').then((val) => { expect(val).to.be.equal('http://hello'
    page.captcha_path.invoke('val').then((val) => { expect(val).to.be.equal(@upload_path
  }
}
