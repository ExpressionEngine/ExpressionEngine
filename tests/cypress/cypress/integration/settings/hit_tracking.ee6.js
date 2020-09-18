/// <reference types="Cypress" />

import HitTracking from '../../elements/pages/settings/HitTracking';

const page = new HitTracking

context('Hit Tracking', () => {
  beforeEach(function(){
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Hit Tracking page', () => {
    //page.all_there?.should == true
  })

  it('validates the suspend threshold field', () => {
    const is_numeric_error = 'This field must contain only numeric characters.'

    // Ajax testing
    page.get('dynamic_tracking_disabling').clear().type('three')
    page.get('dynamic_tracking_disabling').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('dynamic_tracking_disabling'), is_numeric_error)
    //page.hasErrors()


    // Clean up after Ajax testing
    page.get('dynamic_tracking_disabling').clear().type('3')
    page.get('dynamic_tracking_disabling').blur()
    //page.hasErrorsCount(0)

    // Form Validation
    page.get('dynamic_tracking_disabling').clear().type('three')
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    cy.wait(400)
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('dynamic_tracking_disabling'), is_numeric_error)
  })

  it('saves settings on page load', () => {

    let enable_online_user_tracking, enable_hit_tracking, enable_entry_view_tracking
    cy.eeConfig({item: 'enable_online_user_tracking'}) .then((config) => {
      enable_online_user_tracking = config
    })
    cy.eeConfig({item: 'enable_hit_tracking'}) .then((config) => {
      enable_hit_tracking = config
    })
    cy.eeConfig({item: 'enable_entry_view_tracking'}) .then((config) => {
      enable_entry_view_tracking = config
    })

    page.get('enable_online_user_tracking_toggle').click()
    page.get('enable_hit_tracking_toggle').click()
    page.get('enable_entry_view_tracking_toggle').click()
    page.get('dynamic_tracking_disabling').clear().type('360')
    //page.submit()AJ
    cy.get('input').contains('Save Settings').first().click()

    cy.hasNoErrors()
    page.get('wrap').should('not.contain', 'Attention: Settings not saved')
    page.get('enable_online_user_tracking').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_online_user_tracking)
    })
    page.get('enable_hit_tracking').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_hit_tracking)
    })
    page.get('enable_entry_view_tracking').invoke('val').then((val) => {
      expect(val).not.to.be.equal(enable_entry_view_tracking)
    })
    page.get('dynamic_tracking_disabling').invoke('val').then((val) => { expect(val).to.be.equal('360') })
  })
})
