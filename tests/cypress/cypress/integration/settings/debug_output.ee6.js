/// <reference types="Cypress" />

import DebugOutput from '../../elements/pages/settings/DebugOutput';

const page = new DebugOutput

context('Debugging & Output Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Debugging & Output Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

    // This is ridiculous -- testing *each* radio button's status
    cy.eeConfig({item: 'debug'}) .then((config) => {
      page.get('debug').filter('[value='+parseInt(config)+']').should('be.checked')
    })
    cy.eeConfig({item: 'show_profiler'}) .then((config) => {
      page.get('show_profiler').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'enable_devlog_alerts'}) .then((config) => {
      page.get('enable_devlog_alerts').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'gzip_output'}) .then((config) => {
      page.get('gzip_output').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'force_query_string'}) .then((config) => {
      page.get('force_query_string').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'send_headers'}) .then((config) => {
      page.get('send_headers').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'redirect_method'}) .then((config) => {
      page.get('redirect_method').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'cache_driver'}) .then((config) => {
      page.get('cache_driver').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'max_caches'}) .then((config) => {
      page.get('max_caches').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  it('should validate the form', () => {
    const max_caches_error = 'This field must contain an integer.'

    page.get('max_caches').clear().type('sdfsdfsd')
    cy.get('input').contains('Save Settings').first().click()

    cy.hasNoErrors()
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.get('wrap').contains(max_caches_error)

    // AJAX validation


    page.load()
    page.get('max_caches').clear().type('sdfsdfsd')
    page.get('max_caches').blur()


//should_have_form_errors(page)
    page.hasError(page.get('max_caches'), max_caches_error)

    page.get('max_caches').clear().type('100')
    page.get('max_caches').blur()

    page.get('wrap').should('not.contain', 'Attention')
    //page.hasErrorsCount(0)
    //should_have_no_form_errors(page)
  })

  it('should save and load the settings', () => {

    let show_profiler, enable_devlog_alerts, gzip_output, force_query_string, send_headers
    cy.eeConfig({item: 'show_profiler'}) .then((config) => {
      show_profiler = config
    })
    cy.eeConfig({item: 'enable_devlog_alerts'}) .then((config) => {
      enable_devlog_alerts = config
    })
    cy.eeConfig({item: 'gzip_output'}) .then((config) => {
      gzip_output = config
    })
    cy.eeConfig({item: 'force_query_string'}) .then((config) => {
      force_query_string = config
    })
    cy.eeConfig({item: 'send_headers'}) .then((config) => {
      send_headers = config
    })

    page.get('debug').filter('[value=0]').check()
    page.get('show_profiler_toggle').click()
    page.get('enable_devlog_alerts_toggle').click()
    page.get('gzip_output_toggle').click()
    page.get('force_query_string_toggle').click()
    page.get('send_headers_toggle').click()
    page.get('cache_driver').filter('[value=memcached]').check()
    page.get('max_caches').clear().type('300')
    //page.submit()AJ
    cy.get('input').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences updated')
    page.get('debug').filter('[value=0]').should('be.checked')
    page.get('show_profiler').invoke('val').then((val) => {
      expect(val).not.to.be.equal(show_profiler)
    })
    page.get('enable_devlog_alerts').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_devlog_alerts)
    })
    page.get('gzip_output').invoke('val').then((val) => {
      expect(val).not.to.be.equal(gzip_output)
    })
    page.get('force_query_string').invoke('val').then((val) => {
      expect(val).not.to.be.equal(force_query_string)
    })
    page.get('send_headers').invoke('val').then((val) => {
      expect(val).not.to.be.equal(send_headers)
    })
    page.get('cache_driver').filter('[value=memcached]').should('be.checked')
    page.get('max_caches').invoke('val').then((val) => { expect(val).to.be.equal('300') })

    // Should show a message when the selected caching driver
    // cannot be initialized
    page.get('wrap').contains('Cannot connect to Memcached, using File driver instead.')

    // Reset debug and cache_driver since they're only stored in config.php
    cy.eeConfig({item: 'cache_driver', value: 'file'})
    cy.eeConfig({item: 'debug', value: '1'})
  })
})
