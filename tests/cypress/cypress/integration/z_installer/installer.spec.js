/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';
import Form from '../../elements/pages/installer/_install_form';
import Success from '../../elements/pages/installer/_install_success';
import UrlsSettings from '../../elements/pages/settings/UrlsSettings';
import MessagingSettings from '../../elements/pages/settings/MessagingSettings';
import CaptchaSettings from '../../elements/pages/settings/CaptchaSettings';

const page = new Installer
const install_form = new Form
const install_success = new Success

context('Installer', () => {
  before(function() {

  })

  beforeEach(function(){
    // Delete existing config and create a new one
    cy.task('db:clear')
    cy.task('cache:clear')
    cy.task('installer:enable').then(() => {

      let installer_folder = '../../system/ee/installer';
      cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
        for (const item in files) {
          cy.log(files[item]);
          if (files[item].indexOf('system/ee/installer_') >= 0) {
            installer_folder = files[item];
            cy.log(installer_folder);
            cy.task('filesystem:delete', '../../system/ee/installer').then(()=>{
              cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer'})
            })
          }
        }
        cy.task('installer:create_config').then((path)=>{
          cy.log(path)
          //cy.screenshot({capture: 'runner'})
        })

        cy.task('filesystem:delete', '../../system/user/cache/mailing_list.zip')

        cy.wait(5000)

        page.load()
        cy.screenshot({capture: 'runner'})
        cy.screenshot({capture: 'fullPage'})
        cy.hasNoErrors()
      })
    })
  })

  after(function(){
    cy.task('installer:disable')
    //cy.task('installer:revert_config')
  })

  function install_complete() {
    page.get('header').invoke('text').then((text) => {
      expect(text).to.be.oneOf([ "Install Complete!", "Log In", "Log into Default Site" ])
      if (text == "Install Complete!") {
        install_success.get('updater_msg').contains("ExpressionEngine has been installed")
        for (const el in install_success.all_there) {
          cy.get(install_success.all_there[el]).should('exist')
        }
      } else {
        cy.contains('Username');
        cy.contains('Password');
        cy.contains('Remind me');

        cy.get('input[type=submit]').should('not.be.disabled');
      }
    })
  }

  it('loads', () => {
    page.get('inline_errors').should('not.exist')
    for (const el in install_form.all_there) {
      cy.get(install_form.all_there[el]).should('exist')
    }
  })

  context('when installing', () => {
    it('installs successfully using 127.0.0.1 as the database host', () => {
      install_form.get('db_hostname').clear().type('127.0.0.1')
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
        install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      install_complete();
    })

    it('installs successfully using localhost as the database host', () => {
      install_form.get('db_hostname').clear().type('localhost')
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
        install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      install_complete();
    })

    it('installs successfully with the default theme', () => {
      cy.task('installer:backup_templates').then(() => {

        install_form.get('db_hostname').clear().type('localhost')
        install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
        install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
        install_form.get('db_password').clear()
        if (Cypress.env("DB_PASSWORD") != '') {
          install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
        }

        install_form.get('install_default_theme').click()
        install_form.get('username').clear().type('admin')
        install_form.get('email_address').clear().type('hello@expressionengine.com')
        install_form.get('password').clear().type('password')
        install_form.get('license_agreement').click()
        install_form.get('install_submit').click()

        cy.hasNoErrors()
        install_complete();

        cy.task('installer:restore_templates')
      })
    })

    it('has all require modules installed after installation', () => {
      install_form.get('db_hostname').clear().type('127.0.0.1')
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
        install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      install_complete();

      let installed_modules = []
      cy.task('db:query', 'SELECT module_name FROM exp_modules').then((result) => {
        result[0].forEach(function(row){
          installed_modules.push(row.module_name.toLowerCase());
        });

        expect(installed_modules).to.include('channel')
        expect(installed_modules).to.include('comment')
        expect(installed_modules).to.include('member')
        expect(installed_modules).to.include('stats')
        expect(installed_modules).to.include('rte')
        expect(installed_modules).to.include('file')
        expect(installed_modules).to.include('filepicker')
        expect(installed_modules).to.include('search')
      })
    })

    it('uses {base_url} and {base_path}', () => {
      install_form.get('db_hostname').clear().type('127.0.0.1')
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
        install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      install_complete();
      cy.task('installer:disable').then(()=>{

        //   print @env
        //   print File.read(@env)
        let installer_folder = '../../system/ee/installer';
        cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
          for (const item in files) {
            if (files[item].indexOf('system/ee/installer') >= 0) {
              installer_folder = files[item];
              cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer_old'}).then(() => {

                //   print @config
                //   print File.read(@config)
                cy.get('body').then(($body) => {
                  if ($body.find('p.msg-choices a').length) {
                    install_success.get('login_button').click()
                  }
                  cy.login();

                  let settings = new UrlsSettings
                  settings.load()

                  settings.get('base_url').invoke('val').then((val) => { expect(val).to.contain(Cypress.config().baseUrl) })
                  settings.get('base_path').invoke('val').then((val) => { expect(val).to.not.be.equal('') })
                  settings.get('site_url').invoke('val').then((val) => { expect(val).to.include('{base_url}') })
                  settings.get('cp_url').invoke('val').then((val) => { expect(val).to.include('{base_url}') })
                  settings.get('theme_folder_url').invoke('val').then((val) => { expect(val).to.include('{base_url}') })
                  settings.get('theme_folder_path').invoke('val').then((val) => { expect(val).to.include('{base_path}') })

                  settings = new MessagingSettings
                  settings.load()

                  settings.get('prv_msg_upload_url').invoke('val').then((val) => { expect(val).to.include('{base_url}') })
                  settings.get('prv_msg_upload_path').invoke('val').then((val) => { expect(val).to.include('{base_path}') })

                  settings = new CaptchaSettings
                  settings.load()

                  settings.get('captcha_url').invoke('val').then((val) => { expect(val).to.include('{base_url}') })
                  settings.get('captcha_path').invoke('val').then((val) => { expect(val).to.include('{base_path}') })

                  cy.task('filesystem:rename', {from: '../../system/ee/installer_old', to: '../../system/ee/installer'})
                })

              })
            }
          }
        })

      })
    })

  })

  context('when using invalid database credentials', () => {
    it('shows an error with no database credentials', () => {
      cy.wait(5000) //just wait a bit if not everything is clean from previous test
      page.load()
      
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      for (const el in install_form.all_there) {
        cy.get(install_form.all_there[el]).should('exist')
      }
      page.get('inline_errors').its('length').should('gte', 1)
    })

    it('shows an inline error when using an incorrect database host', () => {
      install_form.get('db_hostname').clear().type('nonsense')
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      for (const el in install_form.all_there) {
        cy.get(install_form.all_there[el]).should('exist')
      }
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('The database host you submitted is invalid.')
    })

    it('shows an inline error when using an incorrect database name', () => {
      install_form.get('db_hostname').clear().type(Cypress.env("DB_HOST"))
      install_form.get('db_name').clear().type('nonsense')
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      for (const el in install_form.all_there) {
        cy.get(install_form.all_there[el]).should('exist')
      }
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('The database name you submitted is invalid.')
    })

    it('shows an error when using an incorrect database user', () => {
      install_form.get('db_hostname').clear().type(Cypress.env("DB_HOST"))
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type('nonsense')
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      for (const el in install_form.all_there) {
        cy.get(install_form.all_there[el]).should('exist')
      }
      page.get('inline_errors').should('not.exist')
      page.get('error').contains('The database user and password combination you submitted is invalid.')
    })
  })

  context('when using an invalid database prefix', () => {
    it('shows an error when the database prefix is too long', () => {
      cy.window().then((win) => {
        win.$('input[maxlength=30]').prop('maxlength', 80);
      })
      install_form.get('db_prefix').clear().type('1234567890123456789012345678901234567890')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('This field cannot exceed')
      page.get('inline_errors').contains('characters in length')
    })

    it('shows an error when using invalid characters in the database prefix', () => {
      install_form.get('db_prefix').clear().type('<nonsense>')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('There are invalid characters in the database prefix.')
    })

    it('shows an error when using exp_ in the database prefix', () => {
      install_form.get('db_prefix').clear().type('exp_')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('The database prefix cannot contain the string "exp_".')
    })
  })

  context('when using an invalid username', () => {
    it('shows an error when using invalid characters', () => {
      install_form.get('username').clear().type('non<>sense')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('Your username cannot use the following characters:')
    })

    it('shows an error when using a too-short username', () => {
      install_form.get('username').clear().type('123')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('Your username must be at least 4 characters long')
    })

    it('shows an error when using a too-long username', () => {
      cy.window().then((win) => {
        win.$('input[maxlength=50]').prop('maxlength', 80);
      })
      install_form.get('username').clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains(/Your username cannot be over \d+ characters in length/)
    })
  })

  context('when using an invalid email address', () => {
    it('shows an error when no domain is supplied', () => {
      install_form.get('email_address').clear().type('nonsense')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('This field must contain a valid email address')
    })

    it('shows an error when no tld is supplied', () => {
      install_form.get('email_address').clear().type('nonsense@example')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('This field must contain a valid email address')
    })

    it('shows an error when no username is supplied', () => {
      install_form.get('email_address').clear().type('example.com')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('This field must contain a valid email address')
    })
  })

  context('when using an invalid password', () => {
    it('shows an error when the password is too short', () => {
      install_form.get('password').clear().type('123')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains(/Your password must be at least \d+ characters long/)
    })

    it('shows an error when the password is too long', () => {
      cy.window().then((win) => {
        win.$('input[maxlength=72]').prop('maxlength', 80);
      })
      install_form.get('password').clear().type('12345678901234567890123456789012345678901234567890123456789012345678901234567890')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains(/Your password cannot be over \d+ characters in length/)
    })

    it('shows an error when the username and password are the same', () => {
      install_form.get('username').clear().type('nonsense')
      install_form.get('password').clear().type('nonsense')
      install_form.get('install_submit').click()
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('The password cannot be based on the username')
    })
  })

  context('when not agreeing to the license agreement', () => {
    it('will not install without the license agreement checked', () => {
      install_form.get('db_hostname').clear().type('127.0.0.1')
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
        install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('password')
      install_form.get('install_submit').click()

      cy.hasNoErrors()
      for (const el in install_form.all_there) {
        cy.get(install_form.all_there[el]).should('exist')
      }
      for (const el in install_success.all_there) {
        cy.get(install_success.all_there[el]).should('not.exist')
      }
      page.get('inline_errors').its('length').should('gte', 1)
      page.get('inline_errors').contains('You must accept the terms and conditions of the license agreement.')
    })
  })
})
