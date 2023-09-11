/// <reference types="Cypress" />

import AvatarSettings from '../../elements/pages/settings/AvatarSettings';

const page = new AvatarSettings

let upload_path

context('Avatar Settings', () => {

  before(function(){
    cy.task('db:seed')
    cy.task('filesystem:path', '../../images').then((path) => {
      upload_path = path
    })
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('should load Avatar Settings into form fields', () => {

    cy.eeConfig({item: 'avatar_url'}) .then((config) => {
      page.get('avatar_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'avatar_path'}) .then((config) => {
      page.get('avatar_path').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'avatar_max_width'}) .then((config) => {
      page.get('avatar_max_width').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'avatar_max_height'}) .then((config) => {
      page.get('avatar_max_height').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'avatar_max_kb'}) .then((config) => {
      page.get('avatar_max_kb').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

  })

  it('Validate Avatar Settings form', () => {
    page.get('avatar_path').clear().type('sdfsdfsd')
   cy.get('[value="Save Settings"]').first().click()

    cy.hasNoErrors()

    page.get('wrap').contains('Attention: Settings not saved')
    page.get('wrap').contains(page.messages.validation.invalid_path)

    // AJAX validation
    page.load()
    page.get('avatar_path').clear().type('sdfsdfsd')
    page.get('avatar_path').blur()
    cy.wait(500);
    page.hasError(page.get('avatar_path'), page.messages.validation.invalid_path)


    page.get('avatar_path').clear().type(upload_path)
    page.get('avatar_path').blur()
    cy.wait(500);


    page.get('avatar_path').clear().type('/')
    page.get('avatar_path').blur()
    cy.wait(500);

    page.hasError(page.get('avatar_path'), page.messages.validation.not_writable)

    page.get('avatar_max_width').clear().type('dfsd')
    page.get('avatar_max_width').blur()
    cy.wait(500);

    page.hasError(page.get('avatar_max_width'), page.messages.validation.integer_error)

    page.get('avatar_max_height').clear().type('dsfsd')
    page.get('avatar_max_height').blur()
    cy.wait(500);

    page.hasError(page.get('avatar_max_height'), page.messages.validation.integer_error)

    page.get('avatar_max_kb').clear().type('sdfsdfsd')
    page.get('avatar_max_kb').blur()

    page.hasError(page.get('avatar_max_kb'), page.messages.validation.integer_error)

    // Fix everything
    page.get('avatar_path').clear().type(upload_path)
    page.get('avatar_path').blur()
    cy.wait(500);

    page.hasNoError(page.get('avatar_path'))

    page.get('avatar_max_width').clear().type('100')
    page.get('avatar_max_width').blur()
    cy.wait(500);

    page.hasNoError(page.get('avatar_max_width'))

    page.get('avatar_max_height').clear().type('100')
    page.get('avatar_max_height').blur()
    cy.wait(500);

    page.hasNoError(page.get('avatar_max_height'))

    page.get('avatar_max_kb').clear().type('100')
    page.get('avatar_max_kb').blur()
    cy.wait(500);

    page.hasNoError(page.get('avatar_max_kb'))
  })

  it('should reject XSS', () => {
    page.get('avatar_url').clear().type(page.messages.xss_vector)
    page.get('avatar_url').blur()
    page.hasError(page.get('avatar_url'), page.messages.xss_error)

    page.get('avatar_path').clear().type(page.messages.xss_vector)
    page.get('avatar_path').blur()
    page.hasError(page.get('avatar_url'), page.messages.xss_error)
    page.hasError(page.get('avatar_path'), page.messages.xss_error)
  })

  it('should save and load Avatar Settings', () => {

    page.get('avatar_url').clear().type('http://hello')
    page.get('avatar_path').clear().type(upload_path)
    page.get('avatar_max_width').clear().type('100')
    page.get('avatar_max_height').clear().type('101')
    page.get('avatar_max_kb').clear().type('102')

    cy.get('[value="Save Settings"]').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('avatar_url').invoke('val').then((val) => { expect(val).to.be.equal('http://hello') })
    page.get('avatar_path').invoke('val').then((val) => { expect(val).to.be.equal(upload_path.replace(/\\/g, '/')) })
    page.get('avatar_max_width').invoke('val').then((val) => { expect(val).to.be.equal('100') })
    page.get('avatar_max_height').invoke('val').then((val) => { expect(val).to.be.equal('101') })
    page.get('avatar_max_kb').invoke('val').then((val) => { expect(val).to.be.equal('102') })
  })
})
