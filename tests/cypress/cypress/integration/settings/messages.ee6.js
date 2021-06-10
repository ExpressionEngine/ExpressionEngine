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

  it('shows the Messaging Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

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

  it('should validate the form', () => {
    page.get('prv_msg_max_chars').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.get('wrap').contains(page.messages.validation.integer_error)

    // AJAX validation
    page.load()
    page.get('prv_msg_max_chars').clear().type('sdfsdfsd')
    page.get('prv_msg_max_chars').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('prv_msg_max_chars'), page.messages.validation.integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('prv_msg_upload_path').clear().type('/dfffds/')
    page.get('prv_msg_upload_path').blur()
    //page.hasErrorsCount(2)
    page.hasError(page.get('prv_msg_upload_path'), page.messages.validation.invalid_path)
   // page.hasErrors()
//should_have_form_errors(page)

    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').clear().type(path)
      page.get('prv_msg_upload_path').blur()
      //page.hasErrorsCount(1)
    })

    page.get('prv_msg_upload_path').clear().type('/')
    page.get('prv_msg_upload_path').blur()
    //page.hasErrorsCount(2)
    page.hasError(page.get('prv_msg_upload_path'), page.messages.validation.not_writable)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('prv_msg_max_attachments').clear().type('sdfsdfsd')
    page.get('prv_msg_max_attachments').blur()
    //page.hasErrorsCount(3)
    page.hasError(page.get('prv_msg_max_attachments'), page.messages.validation.integer_error)
   // page.hasErrors()AJ
//should_have_form_errors(page)

    page.get('prv_msg_attach_maxsize').clear().type('sdfsdfsd')
    page.get('prv_msg_attach_maxsize').blur()
    //page.hasErrorsCount(4)
    page.hasError(page.get('prv_msg_attach_maxsize'), page.messages.validation.integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('prv_msg_attach_total').clear().type('sdfsdfsd')
    page.get('prv_msg_attach_total').blur()
    //page.hasErrorsCount(5)
    page.hasError(page.get('prv_msg_attach_total'), page.messages.validation.integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.get('prv_msg_max_chars').clear().type('100')
    page.get('prv_msg_max_chars').blur()
    //page.hasErrorsCount(4)
    page.hasNoError(page.get('prv_msg_max_chars'))
    //page.hasErrors()
//should_have_form_errors(page)

    cy.task('filesystem:path', 'support/tmp').then((path) => {
      page.get('prv_msg_upload_path').clear().type(path)
      page.get('prv_msg_upload_path').blur()
     // page.hasErrorsCount(3)
      page.hasNoError(page.get('prv_msg_upload_path'))
     // page.hasErrors()
      //should_have_form_errors(page)
    })

    page.get('prv_msg_max_attachments').clear().type('100')
    page.get('prv_msg_max_attachments').blur()
    //page.hasErrorsCount(2)
    page.hasNoError(page.get('prv_msg_max_attachments'))
    //page.hasErrors()
    //should_have_form_errors(page)

    page.get('prv_msg_attach_maxsize').clear().type('100')
    page.get('prv_msg_attach_maxsize').blur()
    //page.hasErrorsCount(1)
    page.hasNoError(page.get('prv_msg_attach_maxsize'))
    //page.hasErrors()
    //should_have_form_errors(page)

    page.get('prv_msg_attach_total').clear().type('100')
    page.get('prv_msg_attach_total').blur()
    //page.hasErrorsCount(0)
    page.hasNoError(page.get('prv_msg_attach_total'))
   // page.hasNoErrors()
  })

  it('should reject XSS', () => {
    page.get('prv_msg_upload_path').clear().type(page.messages.xss_vector)
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.hasError(page.get('prv_msg_upload_path'), page.messages.xss_error)
    //page.hasErrors()
//should_have_form_errors(page)
  })

  it('should save and load the settings', () => {
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

    page.get('wrap').contains('Preferences updated')
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
