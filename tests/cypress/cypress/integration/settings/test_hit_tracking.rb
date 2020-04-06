require './bootstrap.rb'

context('Hit Tracking', () => {
  beforeEach(function(){
    cy.auth();
    page = HitTracking.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Hit Tracking page', () => {
    page.all_there?.should == true
  }

  it('validates the suspend threshold field', () => {
    is_numeric_error = 'This field must contain only numeric characters.'

    // Ajax testing
    page.dynamic_tracking_disabling.clear().type('three'
    page.dynamic_tracking_disabling.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.dynamic_tracking_disabling, is_numeric_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Clean up after Ajax testing
    page.dynamic_tracking_disabling.clear().type('3'
    page.dynamic_tracking_disabling.blur()
    page.wait_for_error_message_count(0)

    // Form Validation
    page.dynamic_tracking_disabling.clear().type('three'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.dynamic_tracking_disabling, is_numeric_error)
  }

  it('saves settings on page load', () => {
    enable_online_user_tracking = eeConfig({item: 'enable_online_user_tracking')
    enable_hit_tracking = eeConfig({item: 'enable_hit_tracking')
    enable_entry_view_tracking = eeConfig({item: 'enable_entry_view_tracking')

    page.enable_online_user_tracking_toggle.click()
    page.enable_hit_tracking_toggle.click()
    page.enable_entry_view_tracking_toggle.click()
    page.dynamic_tracking_disabling.clear().type('360'
    page.submit

    cy.hasNoErrors()
    page.should_not have_text 'Attention: Settings not saved'
    page.enable_online_user_tracking.value.should_not == enable_online_user_tracking
    page.enable_hit_tracking.value.should_not == enable_hit_tracking
    page.enable_entry_view_tracking.value.should_not == enable_entry_view_tracking
    page.dynamic_tracking_disabling.invoke('val').then((val) => { expect(val).to.be.equal('360'
  }
}
