require './bootstrap.rb'

context('Watermark Create/Edit', () => {

  beforeEach(function() {
    cy.auth();
    page = WatermarkEdit.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Watermark Create/Edit page', () => {
    page.get('wrap').contains('Create Watermark'
  }

  it('should validate fields', () => {
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('alert_error').should('be.visible')
    page.hasError(page.wm_name, $required_error)

    // AJAX validation
    // Required name
    page.load()
    page.wm_name.clear()
    page.wm_name.blur()
    page.hasErrorsCount(1)
    page.hasError(page.wm_name, $required_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Text required when watermark type is text
    page.wm_text.clear()
    page.wm_text.blur()
    page.hasErrorsCount(2)
    page.hasError(page.wm_text, $required_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Numbers
    page.wm_padding.clear().type('sdfsd'
    page.wm_padding.blur()
    page.hasErrorsCount(3)
    page.hasError(page.wm_padding, $natural_number)
    page.hasErrors()
//should_have_form_errors(page)

    page.wm_font_size.clear().type('sdfsd'
    page.wm_font_size.blur()
    page.hasErrorsCount(4)
    page.hasError(page.wm_font_size, $natural_number_not_zero)
    page.hasErrors()
//should_have_form_errors(page)

    page.wm_shadow_distance.clear().type('sdfsd'
    page.wm_shadow_distance.blur()
    page.hasErrorsCount(5)
    page.hasError(page.wm_shadow_distance, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Hex colors
    page.wm_shadow_color.clear().type('sdfsd'
    page.wm_shadow_color.blur()
    page.hasErrorsCount(6)
    page.hasError(page.wm_shadow_color, $hex_color)
    page.hasErrors()
//should_have_form_errors(page)

    page.wm_font_color.clear().type('sdfsd'
    page.wm_font_color.blur()
    page.hasErrorsCount(7)
    page.hasError(page.wm_font_color, $hex_color)
    page.hasErrors()
//should_have_form_errors(page)

    page.wm_type.choose_radio_option 'image'

    page.wait_until_wm_image_path_visible
    page.wait_until_wm_opacity_visible
    page.wait_until_wm_x_transp_visible
    page.wait_until_wm_y_transp_visible
    page.hasErrorsCount(2)

    page.wm_image_path.clear().type('sdfsd'
    page.wm_image_path.blur()
    page.hasErrorsCount(3)
    page.hasError(page.wm_image_path, page.messages.validation.invalid_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.wm_opacity.clear().type('sdfsd'
    page.wm_opacity.blur()
    page.hasErrorsCount(4)
    page.hasError(page.wm_opacity, $natural_number)
    page.hasErrors()
//should_have_form_errors(page)

    // Lots of AJAX going on, make sure there are no JS errors
    cy.hasNoErrors()
  }

  it('should save and load a text watermark', () => {
    page.wm_name.clear().type('Test'
    page.wm_vrt_alignment.choose_radio_option 'middle'
    page.wm_hor_alignment.choose_radio_option 'right'
    page.wm_padding.set 10
    page.wm_hor_offset.set 20
    page.wm_vrt_offset.set 30
    page.wm_use_font.click()
    page.wm_text.clear().type('Test text'
    page.wm_font_size.set 18
    page.wm_font_color.clear().type('ccc'
    page.wm_use_drop_shadow.click()
    page.wm_shadow_distance.set 50
    page.wm_shadow_color.clear().type('000'
    page.submit

    page.get('alert_success').should('be.visible')
    cy.hasNoErrors()

    click_link 'Test'

    cy.hasNoErrors()

    page.wm_name.invoke('val').then((val) => { expect(val).to.be.equal('Test'
    page.wm_type.filter('[value=text').should == true
    page.wm_vrt_alignment.filter('[value=middle').should == true
    page.wm_hor_alignment.filter('[value=right').should == true
    page.wm_padding.invoke('val').then((val) => { expect(val).to.be.equal('10'
    page.wm_hor_offset.invoke('val').then((val) => { expect(val).to.be.equal('20'
    page.wm_vrt_offset.invoke('val').then((val) => { expect(val).to.be.equal('30'
    page.wm_use_font[:class].should include "on"
    page.wm_text.invoke('val').then((val) => { expect(val).to.be.equal('Test text'
    page.wm_font.filter('[value=texb.ttf').should == true
    page.wm_font_size.invoke('val').then((val) => { expect(val).to.be.equal('18'
    page.wm_font_color.invoke('val').then((val) => { expect(val).to.be.equal('ccc'
    page.wm_use_drop_shadow[:class].should include "on"
    page.wm_shadow_distance.invoke('val').then((val) => { expect(val).to.be.equal('50'
    page.wm_shadow_color.invoke('val').then((val) => { expect(val).to.be.equal('000'
  }

  it('should save and load an image watermark', () => {
    page.wm_name.clear().type('Test'
    page.wm_type.choose_radio_option 'image'

    page.wait_until_wm_image_path_visible
    page.wait_until_wm_opacity_visible
    page.wait_until_wm_x_transp_visible
    page.wait_until_wm_y_transp_visible

    page.wm_vrt_alignment.choose_radio_option 'bottom'
    page.wm_hor_alignment.choose_radio_option 'center'
    page.wm_padding.set 10
    page.wm_hor_offset.set 20
    page.wm_vrt_offset.set 30

    path = File.expand_path('support/file-sync/images/8bit_kevin.png')
    page.wm_image_path.set path
    page.wm_opacity.set 40
    page.wm_x_transp.set 50
    page.wm_y_transp.set 60
    page.submit

    page.get('alert_success').should('be.visible')
    cy.hasNoErrors()

    click_link 'Test'

    cy.hasNoErrors()

    page.wm_name.invoke('val').then((val) => { expect(val).to.be.equal('Test'
    page.wm_type.filter('[value=image').should == true
    page.wm_vrt_alignment.filter('[value=bottom').should == true
    page.wm_hor_alignment.filter('[value=center').should == true
    page.wm_padding.invoke('val').then((val) => { expect(val).to.be.equal('10'
    page.wm_hor_offset.invoke('val').then((val) => { expect(val).to.be.equal('20'
    page.wm_vrt_offset.invoke('val').then((val) => { expect(val).to.be.equal('30'
    page.wm_image_path.invoke('val').then((val) => { expect(val).to.be.equal(path
    page.wm_opacity.invoke('val').then((val) => { expect(val).to.be.equal('40'
    page.wm_x_transp.invoke('val').then((val) => { expect(val).to.be.equal('50'
    page.wm_y_transp.invoke('val').then((val) => { expect(val).to.be.equal('60'
  }

  it('should reject XSS', () => {
    page.wm_name.clear().type(page.messages.xss_vector)
    page.wm_name.blur()
    page.hasErrorsCount(1)
    page.hasError(page.wm_name, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
  }

}
