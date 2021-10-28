/// <reference types="Cypress" />

import CaptchaSettings from '../../elements/pages/settings/CaptchaSettings';

const page = new CaptchaSettings

let upload_path

context('CAPTCHA Settings', () => {

  before(function(){
    cy.task('filesystem:path', '../../images').then((path) => {
      upload_path = path
    })
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the CAPTCHA Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

    cy.eeConfig({item: 'require_captcha'}) .then((config) => {
      page.get('require_captcha').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'captcha_font'}) .then((config) => {
      page.get('captcha_font').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'captcha_rand'}) .then((config) => {
      page.get('captcha_rand').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'captcha_require_members'}) .then((config) => {
      page.get('captcha_require_members').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'captcha_url'}) .then((config) => {
      page.get('captcha_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'captcha_path'}) .then((config) => {
      page.get('captcha_path').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  it('should validate the form', () => {
    page.get('captcha_path').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()

//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('captcha_path'), page.messages.validation.invalid_path)

    // AJAX validation
    page.load()
    page.get('captcha_path').clear().type('sdfsdfsd')
    page.get('captcha_path').blur()

    page.hasError(page.get('captcha_path'), page.messages.validation.invalid_path)

//should_have_form_errors(page)

    page.get('captcha_path').clear().type(upload_path)
    page.get('captcha_path').blur()


    page.get('captcha_path').clear().type('/')
    page.get('captcha_path').blur()

    page.hasError(page.get('captcha_path'), page.messages.validation.not_writable)

//should_have_form_errors(page)
  })

  it('should reject XSS', () => {
    page.get('captcha_url').clear().type(page.messages.xss_vector)
    page.get('captcha_url').blur()
    page.hasError(page.get('captcha_url'), page.messages.xss_error)
    //page.hasErrors()AJ
//should_have_form_errors(page)

    page.get('captcha_path').clear().type(page.messages.xss_vector)
    page.get('captcha_path').blur()
    page.hasError(page.get('captcha_url'), page.messages.xss_error)
    page.hasError(page.get('captcha_path'), page.messages.xss_error)
    //page.hasErrors()AJ
//should_have_form_errors(page)
  })

  it('should save and load the settings', () => {

    let require_captcha, captcha_font, captcha_rand, captcha_require_members
    cy.eeConfig({item: 'require_captcha'}) .then((config) => {
      require_captcha = config
    })
    cy.eeConfig({item: 'captcha_font'}) .then((config) => {
      captcha_font = config
    })
    cy.eeConfig({item: 'captcha_rand'}) .then((config) => {
      captcha_rand = config
    })
    cy.eeConfig({item: 'captcha_require_members'}) .then((config) => {
      captcha_require_members = config
    })

    page.get('require_captcha_toggle').click()
    page.get('captcha_font_toggle').click()
    page.get('captcha_rand_toggle').click()
    page.get('captcha_require_members_toggle').click()
    page.get('captcha_url').clear().type('http://hello')
    page.get('captcha_path').clear().type(upload_path)
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences updated')
    page.get('require_captcha').invoke('val').then((val) => {
      expect(val).not.to.be.equal(require_captcha)
    })
    page.get('captcha_font').invoke('val').then((val) => {
      expect(val).not.to.be.equal(captcha_font)
    })
    page.get('captcha_rand').invoke('val').then((val) => {
      expect(val).not.to.be.equal(captcha_rand)
    })
    page.get('captcha_require_members').invoke('val').then((val) => {
      expect(val).not.to.be.equal(captcha_require_members)
    })
    page.get('captcha_url').invoke('val').then((val) => { expect(val).to.be.equal('http://hello') })
    page.get('captcha_path').invoke('val').then((val) => { expect(val).to.be.equal(upload_path.replace(/\\/g, '/')) })
  })
})
