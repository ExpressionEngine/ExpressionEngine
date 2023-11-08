/// <reference types="Cypress" />

import TemplateSettings from '../../elements/pages/settings/TemplateSettings';

const page = new TemplateSettings

context('Template Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('Load current Template Settings into form fields', () => {

    cy.eeConfig({item: 'strict_urls'}) .then((config) => {
      page.get('strict_urls').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'site_404'}) .then((config) => {
      page.get('site_404').find('.lots-of-checkboxes__selection').contains(config)
    })
    cy.eeConfig({item: 'save_tmpl_revisions'}) .then((config) => {
      page.get('save_tmpl_revisions').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

  })

  it('Validate Template Settings form', () => {
    page.get('max_tmpl_revisions').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
 
    //should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.get('wrap').contains(page.messages.validation.integer_error)
    page.get('wrap').invoke('text').then((text) => {
      expect(text).not.contains( page.messages.validation.invalid_path)
    })

    // AJAX validation
    page.load()
    page.get('max_tmpl_revisions').clear().type('sdfsdfsd')
    page.get('max_tmpl_revisions').blur()
    //page.hasErrorsCount(1)
    //page.hasErrors()
    //should_have_form_errors(page)
    page.get('wrap').contains(page.messages.validation.integer_error)

    page.get('max_tmpl_revisions').clear().type('100')
    page.get('max_tmpl_revisions').blur()
    //page.hasNoErrors()
  })

  it('Save and load Template Settings', () => {
    page.get('strict_urls_toggle').click()
    page.get('site_404').find('input[value="search/index"]').check()
    page.get('save_tmpl_revisions_toggle').click()
    page.get('max_tmpl_revisions').clear().type('300')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('strict_urls').invoke('val').then((val) => { expect(val).to.be.equal('n') })
    page.get('site_404').find('.lots-of-checkboxes__selection').contains('search/index')
    page.get('save_tmpl_revisions').invoke('val').then((val) => { expect(val).to.be.equal('y') })
    page.get('max_tmpl_revisions').invoke('val').then((val) => { expect(val).to.be.equal('300') })
  })
})
