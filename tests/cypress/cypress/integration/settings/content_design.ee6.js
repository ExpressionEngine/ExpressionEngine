/// <reference types="Cypress" />

import ContentDesign from '../../elements/pages/settings/ContentDesign';

const page = new ContentDesign

context('Content & Design Settings', () => {

  before(function(){
    cy.task('db:seed')
    cy.task('installer:replace_config')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('Load current Content & Design Settings into form fields', () => {

    cy.eeConfig({item: 'new_posts_clear_caches'}) .then((config) => {
      page.get('new_posts_clear_caches').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'enable_sql_caching'}) .then((config) => {
      page.get('enable_sql_caching').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'auto_assign_cat_parents'}) .then((config) => {
      page.get('auto_assign_cat_parents').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'image_resize_protocol'}) .then((config) => {
      page.get('image_resize_protocol').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'image_library_path'}) .then((config) => {
      page.get('image_library_path').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'enable_emoticons'}) .then((config) => {
      page.get('enable_emoticons').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'emoticon_url'}) .then((config) => {
      page.get('emoticon_url').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  context('Validating Content & Design Settings', () => {
    const image_library_path_error = 'This field must contain a valid path to an image processing library if ImageMagick or NetPBM is the selected protocol.'

    it('validates image resize protocol when using ImageMagick', () => {
      // Should only show an error for image library path if ImageMagick or NetPBM are selected
      page.get('image_resize_protocol').filter('[value=imagemagick]').check()
      page.get('image_library_path').clear()
      page.get('image_library_path').blur()
      //page.hasErrorsCount(1)
      //page.hasErrors()AJ
//should_have_form_errors(page)
      page.hasError(page.get('image_library_path'), image_library_path_error)
    })

    it('validates image resize protocol when using NetPBM', () => {
      page.get('image_resize_protocol').filter('[value=netpbm]').check()
      page.get('image_library_path').clear()
      page.get('image_library_path').blur()
      //page.hasErrorsCount(1, 10)
      //page.hasErrors() AJ

      page.hasError(page.get('image_library_path'), image_library_path_error)
    })

    it('validates a nonsense image library path', () => {
      page.get('image_resize_protocol').filter('[value=netpbm]').check()
      page.get('image_library_path').clear().type('dfsdf')
      page.get('image_library_path').blur()
      //page.hasErrorsCount(1)
      //page.hasErrors()AJ

      page.hasError(page.get('image_library_path'), page.messages.validation.invalid_path)
    })

    it('validates a valid set of library and path', () => {
      page.get('image_resize_protocol').filter('[value=gd]').check()
      page.get('image_library_path').clear()
      page.get('image_library_path').blur()
      //page.hasErrorsCount(0)AJ


      page.hasNoError(page.get('image_library_path'))
      //page.hasNoErrors()AJ
    })
  })

  it('should reject XSS', () => {
    page.get('image_library_path').clear().type(page.messages.xss_vector)
    page.get('image_library_path').blur()
    page.hasError(page.get('image_library_path'), page.messages.xss_error)
    //page.hasErrors()AJ

    page.get('emoticon_url').clear().type(page.messages.xss_vector)
    page.get('emoticon_url').blur()
    //page.hasErrorsCount(3)AJ
    page.hasError(page.get('emoticon_url'), page.messages.xss_error)
    //page.hasErrors()AJ
  })

  it('Save and load Content & Design Settings', () => {

    let new_posts_clear_caches, enable_sql_caching, auto_assign_cat_parents, enable_emoticons
    cy.eeConfig({item: 'new_posts_clear_caches'}) .then((config) => {
      new_posts_clear_caches = config
    })
    cy.eeConfig({item: 'enable_sql_caching'}) .then((config) => {
      enable_sql_caching = config
    })
    cy.eeConfig({item: 'auto_assign_cat_parents'}) .then((config) => {
      auto_assign_cat_parents = config
    })
    cy.eeConfig({item: 'enable_emoticons'}) .then((config) => {
      enable_emoticons = config
    })

    page.get('new_posts_clear_caches_toggle').click()
    page.get('enable_sql_caching_toggle').click()
    page.get('auto_assign_cat_parents_toggle').click()
    page.get('image_resize_protocol').filter('[value=imagemagick]').check()
    page.get('image_library_path').clear().type('/')
    page.get('enable_emoticons_toggle').click()
    // Don't test this, we manually override this path in config.php for the tests
    //page.get('emoticon_url').clear().type('http://myemoticons/'
    //page.submit()AJ
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('new_posts_clear_caches').invoke('val').then((val) => {
      expect(val).not.to.be.equal(new_posts_clear_caches)
    })
    page.get('enable_sql_caching').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_sql_caching)
    })
    page.get('auto_assign_cat_parents').invoke('val').then((val) => {
      expect(val).not.to.be.equal(auto_assign_cat_parents)
    })
    page.get('image_resize_protocol').filter('[value=imagemagick]').should('be.checked')
    page.get('image_library_path').invoke('val').then((val) => { expect(val).to.be.equal('/') })
    page.get('enable_emoticons').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_emoticons)
    })
    //page.get('emoticon_url').invoke('val').then((val) => { expect(val).to.be.equal('http://myemoticons/'
  })
})
