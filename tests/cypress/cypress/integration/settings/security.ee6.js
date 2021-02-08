/// <reference types="Cypress" />

import SecuritySettings from '../../elements/pages/settings/SecuritySettings';

const page = new SecuritySettings

context('Security & Privacy Settings', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('shows the Security & Privacy Settings page', () => {
    //page.all_there?.should == true
  })

  it('should load current settings into form fields', () => {

    cy.eeConfig({item: 'cp_session_type'}) .then((config) => {
      page.get('cp_session_type').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'website_session_type'}) .then((config) => {
      page.get('website_session_type').filter('[value='+config+']').should('be.checked')
    })
    cy.eeConfig({item: 'cookie_domain'}) .then((config) => {
      page.get('cookie_domain').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'cookie_path'}) .then((config) => {
      page.get('cookie_path').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'cookie_prefix'}) .then((config) => {
      page.get('cookie_prefix').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'cookie_httponly'}) .then((config) => {
      page.get('cookie_httponly').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'cookie_secure'}) .then((config) => {
      page.get('cookie_secure').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'allow_username_change'}) .then((config) => {
      page.get('allow_username_change').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'un_min_len'}) .then((config) => {
      page.get('un_min_len').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'allow_multi_logins'}) .then((config) => {
      page.get('allow_multi_logins').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'require_ip_for_login'}) .then((config) => {
      page.get('require_ip_for_login').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'password_lockout'}) .then((config) => {
      page.get('password_lockout').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'password_lockout_interval'}) .then((config) => {
      page.get('password_lockout_interval').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'require_secure_passwords'}) .then((config) => {
      page.get('require_secure_passwords').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'pw_min_len'}) .then((config) => {
      page.get('pw_min_len').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'allow_dictionary_pw'}) .then((config) => {
      page.get('allow_dictionary_pw').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'name_of_dictionary_file'}) .then((config) => {
      page.get('name_of_dictionary_file').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'deny_duplicate_data'}) .then((config) => {
      page.get('deny_duplicate_data').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'require_ip_for_posting'}) .then((config) => {
      page.get('require_ip_for_posting').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'xss_clean_uploads'}) .then((config) => {
      page.get('xss_clean_uploads').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'redirect_submitted_links'}) .then((config) => {
      page.get('redirect_submitted_links').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })
    cy.eeConfig({item: 'force_redirect'}) .then((config) => {
      if (config=='') config = 'n'
      page.get('force_interstitial').invoke('val').then((val) => {
        expect(val).to.be.equal(config)
      })
    })


  })

  it('should validate the form', () => {
    const integer_error = 'This field must contain an integer.'

    page.get('un_min_len').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    cy.hasNoErrors()
    //page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('un_min_len'), integer_error)

    // AJAX validation
    page.load()
    page.get('un_min_len').clear().type('sdfsdfsd')
    page.get('un_min_len').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('un_min_len'), integer_error)
   // page.hasErrors()
//should_have_form_errors(page)

    page.get('password_lockout_interval').clear().type('sdfsdfsd')
    page.get('password_lockout_interval').blur()
    //page.hasErrorsCount(2)
    page.hasError(page.get('password_lockout_interval'), integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('pw_min_len').clear().type('sdfsdfsd')
    page.get('pw_min_len').blur()
    //page.hasErrorsCount(3)
    page.hasError(page.get('pw_min_len'), integer_error)
    //page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.get('un_min_len').clear().type('5')
    page.get('un_min_len').blur()
   // page.hasErrorsCount(2)
    page.hasNoError(page.get('un_min_len'))
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('password_lockout_interval').clear().type('15')
    page.get('password_lockout_interval').blur()
    //page.hasErrorsCount(1)
    page.hasNoError(page.get('password_lockout_interval'))
    //page.hasErrors()
//should_have_form_errors(page)

    page.get('pw_min_len').clear().type('8')
    page.get('pw_min_len').blur()
    //page.hasErrorsCount(0)
    page.hasNoError(page.get('pw_min_len'))
    //page.hasNoErrors()
  })

  it('should save and load the settings', () => {

    cy.task('db:seed')
    cy.auth();
    page.load()
    cy.hasNoErrors()

    page.get('cp_session_type').filter('[value=cs]').check()
   // page.submit()
   
   cy.get('input').contains('Save Settings').first().click()

    cy.auth();
    page.load()

    page.get('cp_session_type').filter('[value=s]').check()
    page.get('website_session_type').filter('[value=s]').check()
    page.get('cookie_domain').clear().type('.yourdomain.com')
    page.get('cookie_path').clear().type('blog')
    page.get('cookie_httponly_toggle').click()
    // Changing cookie_secure will boot us out of the CP
    page.get('allow_username_change_toggle').click()
    page.get('un_min_len').clear().type('5')
    page.get('allow_multi_logins_toggle').click()
    page.get('require_ip_for_login_toggle').click()
    page.get('password_lockout_toggle').click()
    page.get('password_lockout_interval').clear().type('15')
    page.get('require_secure_passwords_toggle').click()
    page.get('pw_min_len').clear().type('8')
    page.get('allow_dictionary_pw_toggle').click()
    page.get('name_of_dictionary_file').clear().type('http://dictionary')
    page.get('deny_duplicate_data_toggle').click()
    page.get('require_ip_for_posting_toggle').click()
    page.get('xss_clean_uploads_toggle').click()
    page.get('redirect_submitted_links_toggle').click()

    page.get('force_interstitial_toggle').should('be.visible')
    page.get('force_interstitial_toggle').click()
    //page.submit()
    cy.get('input').contains('Save Settings').first().click()

    // Since we changed session settings, login again
    cy.auth();
    page.load()

    //page.get('wrap').contains('Preferences updated')
    page.get('cp_session_type').filter('[value=s]').should('be.checked')
    page.get('website_session_type').filter('[value=s]').should('be.checked')
    page.get('cookie_domain').invoke('val').then((val) => { expect(val).to.be.equal('.yourdomain.com')})
    page.get('cookie_path').invoke('val').then((val) => { expect(val).to.be.equal('blog')})
    page.get('cookie_httponly').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('allow_username_change').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('un_min_len').invoke('val').then((val) => { expect(val).to.be.equal('5')})
    page.get('allow_multi_logins').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('require_ip_for_login').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('password_lockout').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('password_lockout_interval').invoke('val').then((val) => { expect(val).to.be.equal('15')})
    page.get('require_secure_passwords').invoke('val').then((val) => { expect(val).to.be.equal('y')})
    page.get('pw_min_len').invoke('val').then((val) => { expect(val).to.be.equal('8')})
    page.get('allow_dictionary_pw').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('name_of_dictionary_file').invoke('val').then((val) => { expect(val).to.be.equal('http://dictionary')})
    page.get('deny_duplicate_data').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('require_ip_for_posting').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('xss_clean_uploads').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('redirect_submitted_links').invoke('val').then((val) => { expect(val).to.be.equal('y')})
    page.get('force_interstitial').invoke('val').then((val) => { expect(val).to.be.equal('y')})
  })
})
