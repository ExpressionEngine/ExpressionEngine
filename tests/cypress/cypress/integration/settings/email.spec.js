/// <reference types="Cypress" />

import EmailSettings from '../../elements/pages/settings/EmailSettings';

const page = new EmailSettings

const field_required = 'This field is required.'
const email_invalid = 'This field must contain a valid email address.'
const server_required = 'This field is required for SMTP.'
const natural_number = 'This field must contain a number greater than zero.'

context('Outgoing Email Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function(){
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  context('when validating with page loads', () => {

    it('should load current email settings into form fields', () => {

      cy.eeConfig({item: 'email_newline'}) .then((config) => {
        page.get('email_newline').filter('[value="'+config.replace(/\\n/g, '\\\\n')+'"]').should('be.checked')
      })
      cy.eeConfig({item: 'webmaster_email'}) .then((config) => {
        page.get('webmaster_email').invoke('val').then((val) => {
          expect(val).to.be.equal(config)
        })
      })
      cy.eeConfig({item: 'webmaster_name'}) .then((config) => {
        page.get('webmaster_name').invoke('val').then((val) => {
          expect(val).to.be.equal(config)
        })
      })
      cy.eeConfig({item: 'email_charset'}) .then((config) => {
        page.get('email_charset').invoke('val').then((val) => {
          expect(val).to.be.equal(config)
        })
      })
      cy.eeConfig({item: 'mail_protocol'}) .then((config) => {
        page.get('mail_protocol').filter('[value='+config+']').should('be.checked')
      })

      cy.eeConfig({item: 'mail_format'}) .then((config) => {
        page.get('mail_format').filter('[value='+config+']').should('be.checked')
      })


      // SMTP fields are hidden unless SMTP is selected
      cy.eeConfig({item: 'word_wrap'}) .then((config) => {
        page.get('word_wrap').invoke('val').then((val) => {
          expect(val).to.be.equal(config)
        })
      })

    })

    it('validates SMTP server when that is the selected protocol', () => {
      page.get('mail_protocol').filter('[value=smtp]').check()
      page.submit()

      cy.hasNoErrors()
      page.hasErrors()
//should_have_form_errors(page)
      page.get('wrap').contains('Attention: Settings not saved')
      page.hasError(page.get('smtp_server'), server_required)
    })

    it('should save and load the settings', () => {
      page.get('webmaster_email').clear().type('test@test.com')
      page.get('webmaster_name').clear().type('Trey Anastasio')
      page.get('email_charset').clear().type('somecharset')
      page.get('mail_protocol').filter('[value=smtp]').check()
      page.get('smtp_server').clear().type('google.com')
      page.get('smtp_port').clear().type('587')
      page.get('smtp_username').clear().type('username')
      page.get('smtp_password').clear().type('password')
      page.get('mail_format').filter('[value=html]').check()
      page.get('word_wrap_toggle').click()
      page.submit()

      page.get('wrap').contains('Preferences updated')
      page.get('webmaster_email').invoke('val').then((val) => { expect(val).to.be.equal('test@test.com') })
      page.get('webmaster_name').invoke('val').then((val) => { expect(val).to.be.equal('Trey Anastasio') })
      page.get('email_charset').invoke('val').then((val) => { expect(val).to.be.equal('somecharset') })
      page.get('mail_protocol').filter('[value=smtp]').should('be.checked')
      page.get('smtp_server').invoke('val').then((val) => { expect(val).to.be.equal('google.com') })
      page.get('smtp_port').invoke('val').then((val) => { expect(val).to.be.equal('587') })
      page.get('smtp_username').invoke('val').then((val) => { expect(val).to.be.equal('username') })
      page.get('smtp_password').invoke('val').then((val) => { expect(val).to.be.equal('password') })
      page.get('mail_format').filter('[value=html]').should('be.checked')
      page.get('word_wrap').invoke('val').then((val) => { expect(val).to.be.equal('n') })
    })
  })

  context('when validating using Ajax', () => {
    it('validates mail protocol', () => {
      page.get('mail_protocol').filter('[value=smtp]').check()

      page.get('smtp_server').should('be.visible')
      page.get('smtp_port').should('be.visible')
      page.get('smtp_username').should('be.visible')
      page.get('smtp_password').should('be.visible')
      page.get('email_smtp_crypto').should('be.visible')

      page.get('smtp_server').clear()
      page.get('smtp_server').blur()
      page.hasErrorsCount(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.get('smtp_server'), server_required)
    })

    it('validates webmaster email when using an empty string', () => {
      page.get('webmaster_email').clear()
      page.get('webmaster_email').blur()
      page.hasErrorsCount(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.get('webmaster_email'), field_required)

      page.get('webmaster_email').clear().type('test@test.com')
      page.get('webmaster_email').blur()
      page.hasErrorsCount(0)
      page.hasNoError(page.get('webmaster_email'))
    })

    it('validates webmaster name using a xss vector', () => {
      page.get('webmaster_name').clear().type(page.messages.xss_vector)
      page.get('webmaster_name').blur()
      page.hasErrorsCount(1)
      page.hasErrors()
//should_have_form_errors(page)
      page.hasError(page.get('webmaster_name'), page.messages.xss_error)

      page.get('webmaster_name').clear().type('Trey Anastasio')
      page.get('webmaster_name').blur()
      page.hasErrorsCount(0)
      page.hasNoError(page.get('webmaster_name'))
    })

    it('validates webmaster email when using nonsense', () => {
      page.get('webmaster_email').clear().type('dfsfdsf')
      page.get('webmaster_email').blur()
      page.hasErrorsCount(1)
      page.hasError(page.get('webmaster_email'), email_invalid)

      page.get('webmaster_email').clear().type('test@test.com')
      page.get('webmaster_email').blur()
      page.hasErrorsCount(0)
      page.hasNoError(page.get('webmaster_email'))
    })

    it('validates mail protocol when using PHP mail', () => {
      page.get('mail_protocol').filter('[value=mail]').check()
      page.get('mail_protocol').eq(0).blur()
      page.hasErrorsCount(0)
      //should_have_no_form_errors(page)
    })

    it('validates SMTP port', () => {
      page.get('mail_protocol').filter('[value=smtp]').check()

      page.get('smtp_server').should('be.visible')
      page.get('smtp_port').should('be.visible')
      page.get('smtp_username').should('be.visible')
      page.get('smtp_password').should('be.visible')
      page.get('email_smtp_crypto').should('be.visible')

      page.get('smtp_port').clear().type('abc')
      page.get('smtp_port').blur()
      page.hasErrorsCount(1)
      page.hasError(page.get('smtp_port'), natural_number)

      page.get('smtp_port').clear().type('587')
      page.get('smtp_port').blur()
      page.hasErrorsCount(0)
      //should_have_no_form_errors(page)
      page.hasNoError(page.get('smtp_port'))
    })
  })
})
