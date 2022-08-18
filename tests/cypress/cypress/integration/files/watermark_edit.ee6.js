/// <reference types="Cypress" />

import WatermarkEdit from '../../elements/pages/files/WatermarkEdit';
const page = new WatermarkEdit;

context('Watermark Create/Edit', () => {

  before(function() {
    cy.task('db:seed')
  })
  
  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Watermark Create/Edit page', () => {
    page.get('wrap').contains('Create Watermark')
  })

  it('should validate fields', () => {
    page.submit()

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('alert_error').should('be.visible')
    page.hasError(page.get('wm_name'), page.messages.validation.required)

    // AJAX validation
    // Required name
    page.load()
    page.get('wm_name').clear()
    page.get('wm_name').blur()
    page.hasErrorsCount(1)
    page.hasError(page.get('wm_name'), page.messages.validation.required)
    page.hasErrors()
//should_have_form_errors(page)

    // Text required when watermark type is text
    page.get('wm_text').clear()
    page.get('wm_text').blur()
    page.hasErrorsCount(2)
    page.hasError(page.get('wm_name'), page.messages.validation.required)
    page.hasErrors()
//should_have_form_errors(page)

    // Numbers
    page.get('wm_padding').clear().type('sdfsh')
    page.get('wm_padding').blur()
    page.hasError(page.get('wm_padding'), page.messages.validation.natural_number)
    page.hasErrorsCount(3)
    page.hasErrors()
//should_have_form_errors(page)

    page.get('wm_font_size').clear().type('sdfst')
    page.get('wm_font_size').blur()
    page.hasErrorsCount(4)
    page.hasError(page.get('wm_font_size'), page.messages.validation.natural_number_not_zero)
    page.hasErrors()
//should_have_form_errors(page)

    page.get('wm_use_drop_shadow').click()
    page.get('wm_shadow_distance').clear().type('sdfse')
    page.get('wm_shadow_distance').blur()
    page.hasErrorsCount(5)
    page.hasError(page.get('wm_shadow_distance'), page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Hex colors
    page.get('wm_shadow_color').clear().type('sdfsd1')
    page.get('wm_shadow_color').blur()
    cy.wait(2000)
    page.get('wm_shadow_color').invoke('val').then((val) => { expect(val).to.be.equal('') })

    page.get('wm_font_color').clear().type('sdfsd2')
    page.get('wm_font_color').blur()
    cy.wait(2000)
    page.get('wm_font_color').invoke('val').then((val) => { expect(val).to.be.equal('') })

    page.get('wm_type').check('image')

    page.get('wm_image_path').should('be.visible')
    page.get('wm_opacity').should('be.visible')
    page.get('wm_x_transp').should('be.visible')
    page.get('wm_y_transp').should('be.visible')
    page.hasErrorsCount(2)

    page.get('wm_image_path').clear().type('sdfsd3')
    page.get('wm_image_path').blur()
    cy.wait(2000)
    cy.screenshot({capture: 'fullPage'});
    page.hasError(page.get('wm_image_path'), page.messages.validation.invalid_path)
    page.hasErrorsCount(3)
    page.hasErrors()
//should_have_form_errors(page)

    page.get('wm_opacity').clear().type('sdfsd4')
    page.get('wm_opacity').blur()
    page.hasErrorsCount(4)
    page.hasError(page.get('wm_opacity'), page.messages.validation.natural_number)
    page.hasErrors()
//should_have_form_errors(page)

    // Lots of AJAX going on, make sure there are no JS errors
    cy.hasNoErrors()
  })

  it('should save and load a text watermark', () => {
    page.get('wm_name').clear().type('Test')
    page.get('wm_vrt_alignment').check('middle')
    page.get('wm_hor_alignment').check('right')
    page.get('wm_padding').clear().type('10')
    page.get('wm_hor_offset').clear().type('20')
    page.get('wm_vrt_offset').clear().type('30')
    page.get('wm_use_font').click()
    page.get('wm_text').clear().type('Test text')
    page.get('wm_font_size').clear().type('18')
    page.get('wm_font_color').clear().type('ccc').blur()
    page.get('wm_use_drop_shadow').click()
    page.get('wm_shadow_distance').clear().type('50')
    page.get('wm_shadow_color').clear().type('000').blur()
    page.submit()

    page.get('alert_success').should('be.visible')
    cy.hasNoErrors()

    cy.get('a').contains('Test').click()

    cy.hasNoErrors()

    page.get('wm_name').invoke('val').then((val) => { expect(val).to.be.equal('Test') })
    page.get('wm_type').filter('[value=text]').should('be.checked')
    page.get('wm_vrt_alignment').filter('[value=middle]').should('be.checked')
    page.get('wm_hor_alignment').filter('[value=right]').should('be.checked')
    page.get('wm_padding').invoke('val').then((val) => { expect(val).to.be.equal('10') })
    page.get('wm_hor_offset').invoke('val').then((val) => { expect(val).to.be.equal('20') })
    page.get('wm_vrt_offset').invoke('val').then((val) => { expect(val).to.be.equal('30') })
    page.get('wm_use_font').should('have.class', "on")
    page.get('wm_text').invoke('val').then((val) => { expect(val).to.be.equal('Test text') })
    page.get('wm_font').filter('[value=texb.ttf]').should('be.checked')
    page.get('wm_font_size').invoke('val').then((val) => { expect(val).to.be.equal('18') })
    page.get('wm_font_color').invoke('val').then((val) => { expect(val).to.be.equal('#CCCCCC') })
    page.get('wm_use_drop_shadow').should('have.class', "on")
    page.get('wm_shadow_distance').invoke('val').then((val) => { expect(val).to.be.equal('50') })
    page.get('wm_shadow_color').invoke('val').then((val) => { expect(val).to.be.equal('#000000') })
  })

  it('should save and load an image watermark', () => {
    page.get('wm_name').clear().type('WM2')
    page.get('wm_type').check('image')

    page.get('wm_image_path').should('be.visible')
    page.get('wm_opacity').should('be.visible')
    page.get('wm_x_transp').should('be.visible')
    page.get('wm_y_transp').should('be.visible')

    page.get('wm_vrt_alignment').check('bottom')
    page.get('wm_hor_alignment').check('center')
    page.get('wm_padding').clear().type('10')
    page.get('wm_hor_offset').clear().type('20')
    page.get('wm_vrt_offset').clear().type('30')

    cy.task('filesystem:path', 'support/file-sync/images/8bit_kevin.png').then((path) => {
      page.get('wm_image_path').clear().type(path)
      page.get('wm_opacity').clear().type(40)
      page.get('wm_x_transp').clear().type(50)
      page.get('wm_y_transp').clear().type(60)
      page.submit()

      page.get('alert_success').should('be.visible')
      cy.hasNoErrors()

      cy.get('a').contains('WM2').click()

      cy.hasNoErrors()

      page.get('wm_name').invoke('val').then((val) => { expect(val).to.be.equal('WM2') })
      page.get('wm_type').filter('[value=image]').should('be.checked')
      page.get('wm_vrt_alignment').filter('[value=bottom]').should('be.checked')
      page.get('wm_hor_alignment').filter('[value=center]').should('be.checked')
      page.get('wm_padding').invoke('val').then((val) => { expect(val).to.be.equal('10') })
      page.get('wm_hor_offset').invoke('val').then((val) => { expect(val).to.be.equal('20') })
      page.get('wm_vrt_offset').invoke('val').then((val) => { expect(val).to.be.equal('30') })
      page.get('wm_image_path').invoke('val').then((val) => { expect(val).to.be.equal(path) })
      page.get('wm_opacity').invoke('val').then((val) => { expect(val).to.be.equal('40') })
      page.get('wm_x_transp').invoke('val').then((val) => { expect(val).to.be.equal('50') })
      page.get('wm_y_transp').invoke('val').then((val) => { expect(val).to.be.equal('60') })
    })
  })

  it('should reject XSS', () => {
    page.get('wm_name').clear().type(page.messages.xss_vector)
    page.get('wm_name').blur()
    page.hasErrorsCount(1)
    page.hasError(page.get('wm_name'), page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
  })

})
