require './bootstrap.rb'

feature 'Watermark Create/Edit', () => {

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
    should_have_form_errors(page)
    page.should have_alert_error
    should_have_error_text(page.wm_name, $required_error)

    // AJAX validation
    // Required name
    page.load()
    page.wm_name.clear().type(''
    page.wm_name.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.wm_name, $required_error)
    should_have_form_errors(page)

    // Text required when watermark type is text
    page.wm_text.clear().type(''
    page.wm_text.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.wm_text, $required_error)
    should_have_form_errors(page)

    // Numbers
    page.wm_padding.clear().type('sdfsd'
    page.wm_padding.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.wm_padding, $natural_number)
    should_have_form_errors(page)

    page.wm_font_size.clear().type('sdfsd'
    page.wm_font_size.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_error_text(page.wm_font_size, $natural_number_not_zero)
    should_have_form_errors(page)

    page.wm_shadow_distance.clear().type('sdfsd'
    page.wm_shadow_distance.trigger 'blur'
    page.wait_for_error_message_count(5)
    should_have_error_text(page.wm_shadow_distance, $integer_error)
    should_have_form_errors(page)

    // Hex colors
    page.wm_shadow_color.clear().type('sdfsd'
    page.wm_shadow_color.trigger 'blur'
    page.wait_for_error_message_count(6)
    should_have_error_text(page.wm_shadow_color, $hex_color)
    should_have_form_errors(page)

    page.wm_font_color.clear().type('sdfsd'
    page.wm_font_color.trigger 'blur'
    page.wait_for_error_message_count(7)
    should_have_error_text(page.wm_font_color, $hex_color)
    should_have_form_errors(page)

    page.wm_type.choose_radio_option 'image'

    page.wait_until_wm_image_path_visible
    page.wait_until_wm_opacity_visible
    page.wait_until_wm_x_transp_visible
    page.wait_until_wm_y_transp_visible
    page.wait_for_error_message_count(2)

    page.wm_image_path.clear().type('sdfsd'
    page.wm_image_path.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.wm_image_path, $invalid_path)
    should_have_form_errors(page)

    page.wm_opacity.clear().type('sdfsd'
    page.wm_opacity.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_error_text(page.wm_opacity, $natural_number)
    should_have_form_errors(page)

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

    page.should have_alert_success
    cy.hasNoErrors()

    click_link 'Test'

    cy.hasNoErrors()

    page.wm_name.value.should == 'Test'
    page.wm_type.has_checked_radio('text').should == true
    page.wm_vrt_alignment.has_checked_radio('middle').should == true
    page.wm_hor_alignment.has_checked_radio('right').should == true
    page.wm_padding.value.should == '10'
    page.wm_hor_offset.value.should == '20'
    page.wm_vrt_offset.value.should == '30'
    page.wm_use_font[:class].should include "on"
    page.wm_text.value.should == 'Test text'
    page.wm_font.has_checked_radio('texb.ttf').should == true
    page.wm_font_size.value.should == '18'
    page.wm_font_color.value.should == 'ccc'
    page.wm_use_drop_shadow[:class].should include "on"
    page.wm_shadow_distance.value.should == '50'
    page.wm_shadow_color.value.should == '000'
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

    page.should have_alert_success
    cy.hasNoErrors()

    click_link 'Test'

    cy.hasNoErrors()

    page.wm_name.value.should == 'Test'
    page.wm_type.has_checked_radio('image').should == true
    page.wm_vrt_alignment.has_checked_radio('bottom').should == true
    page.wm_hor_alignment.has_checked_radio('center').should == true
    page.wm_padding.value.should == '10'
    page.wm_hor_offset.value.should == '20'
    page.wm_vrt_offset.value.should == '30'
    page.wm_image_path.value.should == path
    page.wm_opacity.value.should == '40'
    page.wm_x_transp.value.should == '50'
    page.wm_y_transp.value.should == '60'
  }

  it('should reject XSS', () => {
    page.wm_name.set $xss_vector
    page.wm_name.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.wm_name, $xss_error)
    should_have_form_errors(page)
  }

}
