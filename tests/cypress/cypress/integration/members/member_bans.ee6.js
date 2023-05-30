/// <reference types="Cypress" />

import BansMembers from '../../elements/pages/members/BansMembers';

const page = new BansMembers

context('Ban Settings', () => {
    before(function(){
        cy.task('db:seed')
    })
    
  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Ban Settings page', () => {
    //page.all_there?.should('eq', true
  })

  it('should load current settings into form fields', () => {
    cy.eeConfig({item: 'banned_ips'}) .then((config) => {
      page.get('banned_ips').invoke('text').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'banned_emails'}) .then((config) => {
      page.get('banned_emails').invoke('text').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'banned_usernames'}) .then((config) => {
      page.get('banned_usernames').invoke('text').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })

    cy.eeConfig({item: 'banned_screen_names'}) .then((config) => {
      page.get('banned_screen_names').invoke('text').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'ban_action_options'}) .then((config) => {
      if (config!='') {
        page.get('ban_action_options').find('input[type!=hidden][name=ban_action][value='+config+']').should('be.checked')
      }
    })
    cy.eeConfig({item: 'ban_message'}) .then((config) => {
      page.get('ban_message').invoke('text').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'ban_destination'}).then((config) => {
      page.get('ban_destination').invoke('val').then((val) => {
        expect(val.trim()).to.be.equal(config)
      })
    })
  })

  it('should reject XSS', () => {
    page.get('banned_ips').clear().type(page.messages.xss_vector)
    page.get('banned_ips').blur()
    page.hasError(page.get('banned_ips'), page.messages.xss_error)

    page.get('banned_emails').clear().type(page.messages.xss_vector)
    page.get('banned_emails').blur()
    page.hasError(page.get('banned_emails'), page.messages.xss_error)
    page.hasError(page.get('banned_ips'), page.messages.xss_error)
    
//should_have_form_errors(page)

    page.get('banned_usernames').clear().type(page.messages.xss_vector)
    page.get('banned_usernames').blur()
    
    page.hasError(page.get('banned_usernames'), page.messages.xss_error)
    page.hasError(page.get('banned_emails'), page.messages.xss_error)
    page.hasError(page.get('banned_ips'), page.messages.xss_error)
   
//should_have_form_errors(page)

    page.get('banned_screen_names').clear().type(page.messages.xss_vector)
    page.get('banned_screen_names').blur()
    page.hasError(page.get('banned_screen_names'), page.messages.xss_error)
    page.hasError(page.get('banned_usernames'), page.messages.xss_error)
    page.hasError(page.get('banned_emails'), page.messages.xss_error)
    page.hasError(page.get('banned_ips'), page.messages.xss_error)

//should_have_form_errors(page)
  })

  it('should save and load the settings', () => {
    page.get('banned_ips').clear().type('Dummy IPs')
    page.get('banned_emails').clear().type('Dummy Emails')
    page.get('banned_usernames').clear().type('Dummy Usernames')
    page.get('banned_screen_names').clear().type('Dummy Screen Names')
    page.get('wrap').find('input[type!=hidden][name=ban_action][value=message]').check()
    page.get('ban_message').clear().type('Dummy Message')
    page.get('ban_destination').clear().type('Dummy Destination')
    
    //page.submit() AJ
    cy.get('button').contains('Save Settings').first().click()

    page.get('wrap').contains('Ban Settings updated')
    // Ban settings adds a newline to queue admins for correct legible input
    page.get('banned_ips').invoke('text').then((val) => { expect(val.trim()).to.be.equal("Dummy IPs")})
    page.get('banned_emails').invoke('text').then((val) => { expect(val.trim()).to.be.equal("Dummy Emails")})
    page.get('banned_usernames').invoke('text').then((val) => { expect(val.trim()).to.be.equal("Dummy Usernames")})
    page.get('banned_screen_names').invoke('text').then((val) => { expect(val.trim()).to.be.equal("Dummy Screen Names")})
    page.get('wrap').find('input[type!=hidden][name=ban_action][value=message]').should('be.checked')
    page.get('ban_message').invoke('text').then((val) => { expect(val.trim()).to.be.equal("Dummy Message")})
    page.get('ban_destination').invoke('val').then((val) => { expect(val.trim()).to.be.equal("Dummy Destination")})
  })
})
