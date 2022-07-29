/// <reference types="Cypress" />

import WordCensorship from '../../elements/pages/settings/WordCensorship';

const page = new WordCensorship


context('Word Censorship Settings', () => {

  before(function(){
    cy.task('db:seed')
  })
  
  beforeEach(function() {
    cy.auth();

    page.load()
    cy.hasNoErrors()
  })

  it('shows the Word Censorship Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

    cy.eeConfig({item: 'enable_censoring'}) .then((config) => {
      page.get('enable_censoring').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

    cy.eeConfig({item: 'censor_replacement'}) .then((config) => {
      page.get('censor_replacement').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

    cy.eeConfig({item: 'censored_words'}) .then((config) => {
      page.get('censored_words').invoke('val').then((val) => {
        expect(val).to.be.equal(config.replace('|', "\n"))
      })
    })

  })

  it('should reject XSS', () => {
    page.get('censor_replacement').clear().type(page.messages.xss_vector)
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.hasError(page.get('censor_replacement'), page.messages.xss_error)
    //page.hasErrors()
//should_have_form_errors(page)
  })

  it('should save and load the settings', () => {
    page.get('enable_censoring_toggle').click()
    page.get('censor_replacement').clear().type('####')
    page.get('censored_words').clear().type("Poop\nPerl")
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('enable_censoring').invoke('val').then((val) => { expect(val).to.be.equal('y') })
    page.get('censor_replacement').invoke('val').then((val) => { expect(val).to.be.equal('####') })
    page.get('censored_words').invoke('val').then((val) => { expect(val).to.be.equal("Poop\nPerl") })
  })
})
