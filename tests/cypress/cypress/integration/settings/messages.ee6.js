/// <reference types="Cypress" />

import MessagingSettings from '../../elements/pages/settings/MessagingSettings';

const page = new MessagingSettings


context('Messaging Settings', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('Load current Messaging Settings into form fields', () => {

    cy.eeConfig({item: 'prv_msg_max_chars'}) .then((config) => {
      page.get('prv_msg_max_chars').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'prv_msg_html_format'}) .then((config) => {
      page.get('prv_msg_html_format').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'prv_msg_auto_links'}) .then((config) => {
      page.get('prv_msg_auto_links').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'prv_msg_upload_path'}) .then((config) => {
      page.get('prv_msg_upload_path').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'prv_msg_max_attachments'}) .then((config) => {
      page.get('prv_msg_max_attachments').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'prv_msg_attach_maxsize'}) .then((config) => {
      page.get('prv_msg_attach_maxsize').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'prv_msg_attach_total'}) .then((config) => {
      page.get('prv_msg_attach_total').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  it('Validate Messaging Settings form', () => {
    page.get('prv_msg_max_chars').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
    page.get('wrap').contains('Attention: Settings not saved')
    page.get('wrap').contains(page.messages.validation.integer_error)

    // AJAX validation
    page.load()
    page.get('prv_msg_max_chars').clear().type('sdfsdfsd')
    page.get('prv_msg_max_chars').blur()

    page.hasError(page.get('prv_msg_max_chars'), page.messages.validation.integer_error)
    page.get('prv_msg_upload_path').clear().type('/dfffds/')
    page.get('prv_msg_upload_path').blur()
    page.hasError(page.get('prv_msg_upload_path'), page.messages.validation.invalid_path)

    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').clear().type(path)
      page.get('prv_msg_upload_path').blur()
    })

    page.get('prv_msg_upload_path').clear().type('/')
    page.get('prv_msg_upload_path').blur()
    page.hasError(page.get('prv_msg_upload_path'), page.messages.validation.not_writable)

    page.get('prv_msg_max_attachments').clear().type('sdfsdfsd')
    page.get('prv_msg_max_attachments').blur()
    page.hasError(page.get('prv_msg_max_attachments'), page.messages.validation.integer_error)

    page.get('prv_msg_attach_maxsize').clear().type('sdfsdfsd')
    page.get('prv_msg_attach_maxsize').blur()
    page.hasError(page.get('prv_msg_attach_maxsize'), page.messages.validation.integer_error)

    page.get('prv_msg_attach_total').clear().type('sdfsdfsd')
    page.get('prv_msg_attach_total').blur()
    page.hasError(page.get('prv_msg_attach_total'), page.messages.validation.integer_error)

    // Fix everything
    page.get('prv_msg_max_chars').clear().type('100')
    page.get('prv_msg_max_chars').blur()
    page.hasNoError(page.get('prv_msg_max_chars'))

    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').clear().type(path)
      page.get('prv_msg_upload_path').blur()
      page.hasNoError(page.get('prv_msg_upload_path'))
    })

    page.get('prv_msg_max_attachments').clear().type('100')
    page.get('prv_msg_max_attachments').blur()
    page.hasNoError(page.get('prv_msg_max_attachments'))

    page.get('prv_msg_attach_maxsize').clear().type('100')
    page.get('prv_msg_attach_maxsize').blur()
    page.hasNoError(page.get('prv_msg_attach_maxsize'))

    page.get('prv_msg_attach_total').clear().type('100')
    page.get('prv_msg_attach_total').blur()
    page.hasNoError(page.get('prv_msg_attach_total'))
  })

  it('should reject XSS', () => {
    page.get('prv_msg_upload_path').clear().type(page.messages.xss_vector)
    cy.get('button').contains('Save Settings').first().click()

    page.hasError(page.get('prv_msg_upload_path'), page.messages.xss_error)
  })

  it('Save and load Messaging Settings', () => {
    page.get('prv_msg_max_chars').clear().type('100')
    page.get('prv_msg_html_format').filter('[value=none]').check()
    page.get('prv_msg_auto_links_toggle').click()
    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').clear().type(path)
    })
    page.get('prv_msg_max_attachments').clear().type('101')
    page.get('prv_msg_attach_maxsize').clear().type('102')
    page.get('prv_msg_attach_total').clear().type('103')
   // page.submit()
   cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('prv_msg_max_chars').invoke('val').then((val) => { expect(val).to.be.equal('100')})
    page.get('prv_msg_html_format').filter('[value=none]').should('be.checked')
    page.get('prv_msg_auto_links').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').invoke('val').then((val) => { expect(val).to.be.equal(path.replace(/\\/g, '/')) })
    })
    page.get('prv_msg_max_attachments').invoke('val').then((val) => { expect(val).to.be.equal('101')})
    page.get('prv_msg_attach_maxsize').invoke('val').then((val) => { expect(val).to.be.equal('102')})
    page.get('prv_msg_attach_total').invoke('val').then((val) => { expect(val).to.be.equal('103')})
  })
})
