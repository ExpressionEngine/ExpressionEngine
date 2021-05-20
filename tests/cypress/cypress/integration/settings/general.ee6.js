/// <reference types="Cypress" />

import GeneralSettings from '../../elements/pages/settings/GeneralSettings';

const page = new GeneralSettings

const error_text = 'This field is required.'

context('General Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  afterEach(function() {
    // Reset is_system_on value in config
    cy.eeConfig({item: 'is_system_on', value: 'y'})
  })

  it('shows the General Settings page', () => {
    page.get('wrap').contains('General Settings')
    //page.all_there?.should == true
  })

  it('should load and save the settings', () => {
    // Save new settings
    page.get('site_name').clear().type('My sweet site')
    page.get('site_short_name').clear().type('my_sweet_site')
    page.get('is_system_on_toggle').click()
    page.get('new_version_check').filter('[value=n]').check()
    page.get('check_version_btn').should('exist')
    page.get('date_format').filter('[value="%Y-%m-%d"]').check()
    page.get('time_format').filter('[value=24]').check()
    page.get('include_seconds_toggle').click()

    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    // Make sure they stuck, also test Check Now button visibility
    cy.hasNoErrors()
    //should_have_no_form_errors(page)
    page.get('wrap').contains('Preferences updated')
    page.get('site_name').invoke('val').then((val) => { expect(val).to.be.equal('My sweet site')})
    page.get('site_short_name').invoke('val').then((val) => { expect(val).to.be.equal('my_sweet_site')})
    page.get('is_system_on').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('new_version_check').filter('[value=n]').should('be.checked')
    page.get('check_version_btn').should('exist')
    page.get('date_format').filter('[value="%Y-%m-%d"]').should('be.checked')
    page.get('time_format').filter('[value=24]').should('be.checked')
    page.get('include_seconds').invoke('val').then((val) => { expect(val).to.be.equal('y')})
  })

  it('should check for new versions of EE manually', () => {
    page.get('new_version_check').filter('[value=n]').check()
    page.get('check_version_btn').should('exist')
    page.get('check_version_btn').click()

    // For now, we'll just check to make sure there are no errors
    // getting the latest version info; unsure at the moment how to
    // best handle actual version comparison because we need to edit
    // Core.php dynamically based on the actual latest version
    page.get('alert_error').should('not.exist')
    page.get('wrap').invoke('text').then((text) => {
      expect(text).not.contains( 'An error occurred')
    })
    page.get('wrap').invoke('text').then((text) => {
      expect(text).not.contains( 'Unable to determine if a newer version is available at this time.')
    })
  })

  context('form validation', () => {

    it('should validate with submit', () => {

      cy.task('db:seed')
      cy.auth();
      page.load()
      cy.hasNoErrors()

      // Set other random things to make sure they're repopulated
      page.get('is_system_on_toggle').click()
      page.get('new_version_check').filter('[value=n]').check()
      page.get('check_version_btn').should('exist')
      page.get('date_format').filter('[value="%Y-%m-%d"]').check()
      page.get('time_format').filter('[value=24]').check()
      page.get('include_seconds_toggle').click()

      // Only field that's required, will be our test case
      page.get('site_name').clear()

      //page.submit()
      cy.get('input').contains('Save Settings').first().click()

      cy.hasNoErrors()
     
      //should_have_form_errors(page)
      page.get('wrap').contains('Attention: Settings not saved')
      page.hasError(page.get('site_name'), error_text)
      page.get('is_system_on').invoke('val').then((val) => { expect(val).to.be.equal('n')})
      page.get('new_version_check').filter('[value=n]').should('be.checked')
      page.get('date_format').filter('[value="%Y-%m-%d"]').should('be.checked')
      page.get('time_format').filter('[value=24]').should('be.checked')
      page.get('include_seconds').invoke('val').then((val) => { expect(val).to.be.equal('y') })
    })

    // AJAX validation
    it("should validate with ajax", () => {
      // Make sure old values didn't save after validation error
      //should_have_no_form_errors(page)
      //page.hasNoError(page.get('site_name'))
      cy.hasNoErrors()
      page.get('is_system_on').invoke('val').then((val) => { expect(val).to.be.equal('y') })
      page.get('new_version_check').filter('[value=y]').should('be.checked')
      page.get('date_format').filter('[value="%n/%j/%Y"]').should('be.checked')
      page.get('time_format').filter('[value=12]').should('be.checked')
      page.get('include_seconds').invoke('val').then((val) => { expect(val).to.be.equal('n')})

      // Blank Title
      test_field(page.get('site_name'), '', error_text)
      test_field(page.get('site_name'), 'EE2')

      // Blank Short Name
      test_field(page.get('site_short_name'), '', error_text)
      test_field(page.get('site_short_name'), 'default_site')

      // Short name with spaces
      test_field(page.get('site_short_name'), 'default site', 'This field may only contain alpha-numeric characters, underscores, and dashes.')
      test_field(page.get('site_short_name'), 'default_site')

      // Short name with special characters
      test_field(page.get('site_short_name'), 'default_$ite', 'This field may only contain alpha-numeric characters, underscores, and dashes.')
      test_field(page.get('site_short_name'), 'default_site')

      // XSS
      test_field(page.get('site_name'), '"><script>alert(\'stored xss\')<%2fscript>', page.messages.xss_error)
      test_field(page.get('site_name'), 'EE2')

      test_field(page.get('site_name'), '<script>alert(\'stored xss\')</script>', page.messages.xss_error)
      test_field(page.get('site_name'), 'EE2')
      cy.get('input').contains('Save Settings').first().click()
      //page.submit()
      cy.hasNoErrors()
      //should_have_no_form_errors(page)
      page.get('wrap').contains('Preferences updated')
    })
  })

  // Tests a given field by giving it a value and seeing if the error matches

  // @param field [Object] The field to test
  // @param value [String] The value to set
  // @param error [String] The error message if one is expected, otherwise leave
  //   empty
  function test_field(field, value, error = false) {
    field.clear()
    if (value!='')
    {
      field.type(value)
    }
    field.blur()

    cy.hasNoErrors()

    if (error) {
      //should_have_form_errors(page)
      //page.hasError(field, error)
    }
  }



})
