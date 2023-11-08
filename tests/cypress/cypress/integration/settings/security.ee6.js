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

  it('Load current Security & Privacy Settings into form fields', () => {

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
    cy.eeConfig({item: 'password_security_policy'}) .then((config) => {
      page.get('password_security_policy').filter('[value='+config+']').should('be.checked')
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

  it('Validate Security & Privacy Settings form', () => {
    const integer_error = 'This field must contain an integer.'

    page.get('un_min_len').clear().type('sdfsdfsd')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
    page.get('wrap').contains('Attention: Settings not saved')
    page.hasError(page.get('un_min_len'), integer_error)

    // AJAX validation
    page.load()
    page.get('un_min_len').clear().type('sdfsdfsd')
    page.get('un_min_len').blur()
    //page.hasErrorsCount(1)
    page.hasError(page.get('un_min_len'), integer_error)

    page.get('password_lockout_interval').clear().type('sdfsdfsd')
    page.get('password_lockout_interval').blur()
    //page.hasErrorsCount(2)
    page.hasError(page.get('password_lockout_interval'), integer_error)

    page.get('pw_min_len').clear().type('sdfsdfsd')
    page.get('pw_min_len').blur()
    //page.hasErrorsCount(3)
    page.hasError(page.get('pw_min_len'), integer_error)

    // Fix everything
    page.get('un_min_len').clear().type('5')
    page.get('un_min_len').blur()
   // page.hasErrorsCount(2)
    page.hasNoError(page.get('un_min_len'))

    page.get('password_lockout_interval').clear().type('15')
    page.get('password_lockout_interval').blur()
    //page.hasErrorsCount(1)
    page.hasNoError(page.get('password_lockout_interval'))

    page.get('pw_min_len').clear().type('8')
    page.get('pw_min_len').blur()
    //page.hasErrorsCount(0)
    page.hasNoError(page.get('pw_min_len'))
    //page.hasNoErrors()
  })

  it('Save and load Security & Privacy Settings', () => {

    cy.task('db:seed')
    cy.auth();
    page.load()
    cy.hasNoErrors()

    page.get('cp_session_type').filter('[value=cs]').check()
   // page.submit()
   
   cy.get('button').contains('Save Settings').first().click()

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
    page.get('password_security_policy').filter('[value=strong]').check()
    page.get('pw_min_len').clear().type('8').blur()
    cy.get('.button--primary').contains('Errors Found');
    page.get('pw_min_len').parents('fieldset').should('contain', 'The minimum number of password characters cannot be less than 12 for selected password security policy.')
    page.get('password_security_policy').filter('[value=basic]').check()
    page.get('pw_min_len').focus().blur()
    cy.get('.button--primary').should('not.contain', 'Errors Found');
    page.get('pw_min_len').parents('fieldset').should('not.contain', 'The minimum number of password characters cannot be less than 12 for selected password security policy.')
    page.get('password_security_policy').filter('[value=strong]').check()
    page.get('pw_min_len').focus().blur()
    cy.get('.button--primary').contains('Errors Found');
    page.get('pw_min_len').parents('fieldset').should('contain', 'The minimum number of password characters cannot be less than 12 for selected password security policy.')
    page.get('pw_min_len').clear().type('12').blur()
    cy.get('.button--primary').should('not.contain', 'Errors Found');
    page.get('pw_min_len').parents('fieldset').should('not.contain', 'The minimum number of password characters cannot be less than 12 for selected password security policy.')
    page.get('allow_dictionary_pw_toggle').click()
    page.get('name_of_dictionary_file').clear().type('http://dictionary')
    page.get('deny_duplicate_data_toggle').click()
    page.get('require_ip_for_posting_toggle').click()
    page.get('xss_clean_uploads_toggle').click()
    page.get('redirect_submitted_links_toggle').click()

    cy.get('.button--primary').contains('Errors Found');
    page.get('name_of_dictionary_file').clear().blur()
    cy.get('.button--primary').contains('Errors Found');
    page.get('name_of_dictionary_file').clear().type('http://dictionary').blur()
    cy.get('.button--primary').contains('Errors Found');
    page.get('allow_dictionary_pw_toggle').click()
    page.get('pw_min_len').focus().blur()
    page.get('name_of_dictionary_file').should('not.be.visible')
    cy.get('.button--primary').should('not.contain', 'Errors Found');

    page.get('force_interstitial_toggle').should('be.visible')
    page.get('force_interstitial_toggle').click()
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    // Since we changed session settings, login again
    cy.auth();
    cy.get('h1').should('contain', 'New Access Requirements')
    cy.get('[name="password"]').type('password')
    cy.get('[name="new_password"]:visible').type('123456789012')
    cy.get('[name="new_password_confirm"]:visible').type('123456789012')
    cy.get('.button--primary').click()

    cy.get('body').should('contain', 'The chosen password is not secure enough.')
    cy.get('[name="password"]').type('password')
    cy.get('[name="new_password"]:visible').type('BojVMZA2xj74QGTNzmuL')
    cy.get('[name="new_password_confirm"]:visible').type('BojVMZA2xj74QGTNzmuL')
    cy.get('.button--primary').click()

    cy.login({ email: 'admin', password: 'BojVMZA2xj74QGTNzmuL' });
    page.load()

    //page.get('wrap').contains('Preferences Updated')
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
    page.get('password_security_policy').filter('[value=strong]').should('be.checked')
    page.get('pw_min_len').invoke('val').then((val) => { expect(val).to.be.equal('12')})
    page.get('allow_dictionary_pw').invoke('val').then((val) => { expect(val).to.be.equal('y')})
    page.get('name_of_dictionary_file').invoke('val').then((val) => { expect(val).to.be.equal('http://dictionary')})
    page.get('deny_duplicate_data').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('require_ip_for_posting').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('xss_clean_uploads').invoke('val').then((val) => { expect(val).to.be.equal('n')})
    page.get('redirect_submitted_links').invoke('val').then((val) => { expect(val).to.be.equal('y')})
    page.get('force_interstitial').invoke('val').then((val) => { expect(val).to.be.equal('y')})
  })
})
