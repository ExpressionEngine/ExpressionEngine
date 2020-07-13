/// <reference types="Cypress" />

import MemberSettings from '../../elements/pages/settings/MemberSettings';

const page = new MemberSettings


context('Member Settings', () => {

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Member Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

    cy.eeConfig({item: 'allow_member_registration'}) .then((config) => {
      page.get('allow_member_registration').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'require_terms_of_service'}) .then((config) => {
      page.get('require_terms_of_service').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'allow_member_localization'}) .then((config) => {
      page.get('allow_member_localization').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'req_mbr_activation'}) .then((config) => {
      page.get('req_mbr_activation').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'default_primary_role'}) .then((config) => {
      page.get('default_primary_role').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'member_theme'}) .then((config) => {
      page.get('member_theme').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'memberlist_order_by'}) .then((config) => {
      page.get('memberlist_order_by').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'memberlist_sort_order'}) .then((config) => {
      page.get('memberlist_sort_order').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'memberlist_row_limit'}) .then((config) => {
      page.get('memberlist_row_limit').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'new_member_notification'}) .then((config) => {
      page.get('new_member_notification').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'mbr_notification_emails'}) .then((config) => {
      page.get('mbr_notification_emails').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
  })

  it('should validate the form', () => {
    const emails_error = 'This field must contain all valid email addresses.'

    page.get('mbr_notification_emails').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('mbr_notification_emails'), emails_error)
  })

  it('AJAX form validation', () => {
    const emails_error = 'This field must contain all valid email addresses.'
    page.load()
    page.get('mbr_notification_emails').clear().type('sdfsdfsd')
    page.get('mbr_notification_emails').blur()
    //page.hasErrorsCount(1)AJ
    page.hasError(page.get('mbr_notification_emails'), emails_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('mbr_notification_emails').clear().type('trey@trey.com, test@test.com')
    page.get('mbr_notification_emails').blur()
    //page.hasErrorsCount(0)
    page.hasNoError(page.get('mbr_notification_emails'))
  })

  it('should save and load the settings', () => {

    let allow_member_registration, require_terms_of_service, allow_member_localization, new_member_notification
    cy.eeConfig({item: 'allow_member_registration'}) .then((config) => {
      allow_member_registration = config
    })
    cy.eeConfig({item: 'require_terms_of_service'}) .then((config) => {
      require_terms_of_service = config
    })
    cy.eeConfig({item: 'allow_member_localization'}) .then((config) => {
      allow_member_localization = config
    })
    cy.eeConfig({item: 'new_member_notification'}) .then((config) => {
      new_member_notification = config
    })

    page.get('allow_member_registration_toggle').click()
    page.get('req_mbr_activation').filter('[value=none]').check()
    page.get('require_terms_of_service_toggle').click()
    page.get('allow_member_localization_toggle').click()
    page.get('default_primary_role').filter('[value=1]').check()
    page.get('member_theme').filter('[value=default]').check()
    page.get('memberlist_order_by').filter('[value=dates]').check()
    page.get('memberlist_sort_order').filter('[value=asc]').check()
    page.get('memberlist_row_limit').filter('[value=50]').check()
    page.get('new_member_notification_toggle').click()
    page.get('mbr_notification_emails').clear().type('test@test.com')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Preferences Updated')
    page.get('allow_member_registration').invoke('val').then((val) => {
      expect(val).not.to.be.equal(allow_member_registration)
    })
    page.get('req_mbr_activation').filter('[value=none]').should('be.checked')
    page.get('require_terms_of_service').invoke('val').then((val) => {
      expect(val).not.to.be.equal(require_terms_of_service)
    })
    page.get('allow_member_localization').invoke('val').then((val) => {
      expect(val).not.to.be.equal(allow_member_localization)
    })
    page.get('default_primary_role').filter('[value=1]').should('be.checked')
    page.get('member_theme').filter('[value=default').should('be.checked')
    page.get('memberlist_order_by').filter('[value=dates]').should('be.checked')
    page.get('memberlist_sort_order').filter('[value=asc]').should('be.checked')
    page.get('memberlist_row_limit').filter('[value=50]').should('be.checked')
    page.get('new_member_notification').invoke('val').then((val) => {
      expect(val).not.to.be.equal(new_member_notification)
    })
    page.get('mbr_notification_emails').invoke('val').then((val) => { expect(val).to.be.equal('test@test.com')})
  })
})
