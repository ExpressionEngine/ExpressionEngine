/// <reference types="Cypress" />

import UrlsSettings from '../../elements/pages/settings/UrlsSettings';

const page = new UrlsSettings

context('URL and Path Settings', () => {

  before(function() {
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the URL and Path Settings page', () => {
    page.get('wrap').contains('URL and Path Settings')
    page.get('wrap').contains('Website index page')
    //page.all_there?.should == true
  })

  it('should load current path settings into form fields', () => {

    cy.eeConfig({item: 'site_index'}) .then((config) => {
      page.get('site_index').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'site_url'}) .then((config) => {
      page.get('site_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'cp_url'}) .then((config) => {
      page.get('cp_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'theme_folder_url'}) .then((config) => {
      page.get('theme_folder_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    page.get('theme_folder_path').invoke('val').then((val) => { expect(val).to.be.equal('{base_path}/themes/')})

    cy.eeConfig({item: 'reserved_category_word'}) .then((config) => {
      page.get('category_segment_trigger').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })

    cy.eeConfig({item: 'use_category_name'}) .then((config) => {
      page.get('use_category_name').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'word_separator'}) .then((config) => {
      page.get('url_title_separator').filter('[value='+config+']').should('be.checked')
    })

  })

  it('should only show member trigger if enabled', () => {
    cy.eeConfig({item: 'legacy_member_templates', value: 'y'}).then(() => {
      cy.wait(1000)
      cy.eeConfig({item: 'profile_trigger'}).then((config) => {
        cy.wait(1000)
        expect(config).to.be.not.empty
        page.load()
        cy.hasNoErrors()
        page.get('profile_trigger').invoke('val').then((val) => {
          expect(val).to.be.equal(config)
        })
      })
    })

    cy.eeConfig({item: 'legacy_member_templates', value: 'n'}).then(() => {
      page.load()
      cy.hasNoErrors()
      page.get('profile_trigger').should('not.exist')
    })
    

    
    
  })

  it('should validate the form', () => {
    const field_required = "This field is required."

    page.get('site_url').clear()
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    cy.hasNoErrors()
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('site_url'), field_required)

  })

  it('should validate the form, part 2', () => {
    const field_required = "This field is required."
    // AJAX validation
    // Field not required, shouldn't do anything
    page.get('site_index').clear()
    page.get('site_index').blur()
    page.hasNoErrors()
    //should_have_no_form_errors(page)

    page.get('site_url').clear()
    page.get('site_url').blur()

    page.hasError(page.get('site_url'), field_required)
    page.hasErrors()
    //should_have_form_errors(page)

    page.get('cp_url').clear()
    page.get('cp_url').blur()
    page.hasErrorsCount(2)
    //page.hasErrors()
    //should_have_form_errors(page)
    page.hasError(page.get('site_url'), field_required)
    page.hasError(page.get('cp_url'), field_required)

    page.get('theme_folder_url').clear()
    page.get('theme_folder_url').blur()
    page.hasErrorsCount(3)

    page.get('theme_folder_path').clear()
    page.get('theme_folder_path').blur()
    page.hasErrorsCount(4)

    //page.hasErrors()
    //should_have_form_errors(page)
    page.hasError(page.get('site_url'), field_required)
    page.hasError(page.get('cp_url'), field_required)
    page.hasError(page.get('theme_folder_url'), field_required)
    page.hasError(page.get('theme_folder_path'), field_required)

    page.get('theme_folder_path').clear().type('/')
    // When a text field is invalid, shouldn't need to blur
    // page.get('theme_folder_path').blur()
    page.hasErrorsCount(4)
    // Make sure validation timer is still bound to field
    page.get('theme_folder_path').clear()
    page.hasErrorsCount(4)

    page.get('theme_folder_path').clear().type('{base_path}/themes', { parseSpecialCharSequences: false })
    page.hasErrorsCount(3)

    page.get('theme_folder_path').clear().type('/')
    page.hasErrorsCount(4)
    // Timer should be unbound on blur
    page.get('theme_folder_path').blur()

    // Invalid theme path
    page.get('theme_folder_path').clear().type('/dfsdfsdfd')
    page.get('theme_folder_path').blur()
    page.hasErrorsCount(4)

    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.get('site_url'), field_required)
    page.hasError(page.get('cp_url'), field_required)
    page.hasError(page.get('theme_folder_url'), field_required)
    // TODO: Uncomment when this stops fluking out
    page.hasError(page.get('theme_folder_path'), page.messages.validation.invalid_path)
  })

  it('should reject XSS', () => {
    page.get('site_index').clear().type(page.messages.xss_vector)
    page.get('site_index').blur()
    page.hasErrorsCount(1)
    page.hasError(page.get('site_index'), page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.get('site_url').clear().type(page.messages.xss_vector)
    page.get('site_url').blur()
    page.hasErrorsCount(2)
    page.hasError(page.get('site_url'), page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.get('cp_url').clear().type(page.messages.xss_vector)
    page.get('cp_url').blur()
    page.hasErrorsCount(3)
    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.get('site_url'), page.messages.xss_error)
    page.hasError(page.get('cp_url'), page.messages.xss_error)

    page.get('theme_folder_url').clear().type(page.messages.xss_vector)
    page.get('theme_folder_url').blur()
    page.hasErrorsCount(4)

    page.get('theme_folder_path').clear().type(page.messages.xss_vector)
    page.get('theme_folder_path').blur()
    page.hasErrorsCount(5)

    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.get('site_url'), page.messages.xss_error)
    page.hasError(page.get('cp_url'), page.messages.xss_error)
    page.hasError(page.get('theme_folder_url'), page.messages.xss_error)
    page.hasError(page.get('theme_folder_path'), page.messages.xss_error)
  })

  it('should save and load the settings', () => {
    // We'll test one value for now to make sure the form is saving,
    // don't want to be changing values that could break the site
    // after submission
    page.get('site_index').clear().type('hello.php')
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences updated')
    page.get('site_index').invoke('val').then((val) => { expect(val).to.be.equal('hello.php')})

    // Since this is in config.php, reset the value
    cy.eeConfig({item: 'index_page', value: 'index.php'})
  })
})
