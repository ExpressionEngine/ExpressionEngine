/// <reference types="Cypress" />

import ThrottlingSettings from '../../elements/pages/settings/ThrottlingSettings';

const page = new ThrottlingSettings

context('Access Throttling Settings', () => {

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Access Throttling Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {
    cy.eeConfig({item: 'enable_throttling'}) .then((config) => {
      page.get('enable_throttling').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'banish_masked_ips'}) .then((config) => {
      page.get('banish_masked_ips').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'lockout_time'}) .then((config) => {
      page.get('lockout_time').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'max_page_loads'}) .then((config) => {
      page.get('max_page_loads').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'time_interval'}) .then((config) => {
      page.get('time_interval').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'banishment_type'}) .then((config) => {
      page.get('banishment_type').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'banishment_url'}) .then((config) => {
      page.get('banishment_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'banishment_message'}) .then((config) => {
      page.get('banishment_message').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  it('should validate the form', () => {
    const integer_error = 'This field must contain an integer.'

    page.get('lockout_time').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    cy.hasNoErrors()

//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('lockout_time'), integer_error)

    // AJAX validation
    page.load()
    page.get('lockout_time').clear().type('sdfsdfsd')
    page.get('lockout_time').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('lockout_time'), integer_error)
   // page.hasErrors()
//should_have_form_errors(page)

    page.get('max_page_loads').clear().type('sdfsdfsd')
    page.get('max_page_loads').blur()

    page.hasError(page.get('max_page_loads'), integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('time_interval').clear().type('sdfsdfsd')
    page.get('time_interval').blur()
    //page.hasErrorsCount(3)
    page.hasError(page.get('time_interval'), integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.get('lockout_time').clear().type('5')
    page.get('lockout_time').blur()
   // page.hasErrorsCount(2)
    page.hasNoError(page.get('lockout_time'))
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('max_page_loads').clear().type('15')
    page.get('max_page_loads').blur()
    //page.hasErrorsCount(1)
    page.hasNoError(page.get('max_page_loads'))
   // page.hasErrors()
//should_have_form_errors(page)

    page.get('time_interval').clear().type('8')
    page.get('time_interval').blur()
   // page.hasErrorsCount(0)
    page.hasNoError(page.get('time_interval'))
   // page.hasNoErrors()
  })

  it('should reject XSS', () => {
    page.get('banishment_url').clear().type(page.messages.xss_vector)
    page.get('banishment_url').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('banishment_url'), page.messages.xss_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('banishment_message').clear().type(page.messages.xss_vector)
    page.get('banishment_message').blur()
    //page.hasErrorsCount(2)
    page.hasError(page.get('banishment_url'), page.messages.xss_error)
    page.hasError(page.get('banishment_message'), page.messages.xss_error)
    //page.hasErrors()
//should_have_form_errors(page)
  })

  it('should save and load the settings', () => {
    page.get('enable_throttling_toggle').click()
    page.get('banish_masked_ips_toggle').click()
    page.get('lockout_time').clear().type('60')
    page.get('max_page_loads').clear().type('40')
    page.get('time_interval').clear().type('30')
    page.get('banishment_type').filter('[value=404]').check()
    page.get('banishment_url').clear().type('http://yahoo.com')
    page.get('banishment_message').clear().type('You are banned')
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    page.get('enable_throttling').invoke('val').then((val) => { expect(val).to.be.equal('y')})
    page.get('banish_masked_ips').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('lockout_time').invoke('val').then((val) => { expect(val).to.be.equal('60')})
    page.get('max_page_loads').invoke('val').then((val) => { expect(val).to.be.equal('40')})
    page.get('time_interval').invoke('val').then((val) => { expect(val).to.be.equal('30')})
    page.get('banishment_type').filter('[value=404]').should('be.checked')
    page.get('banishment_url').invoke('val').then((val) => { expect(val).to.be.equal('http://yahoo.com')})
    page.get('banishment_message').invoke('val').then((val) => { expect(val).to.be.equal('You are banned')})
  })
})
