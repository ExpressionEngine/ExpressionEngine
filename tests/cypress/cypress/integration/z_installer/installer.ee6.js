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

        page.load()
        cy.screenshot({capture: 'runner'})
        cy.screenshot({capture: 'fullPage'})
        cy.hasNoErrors()
      })
    })
  })

  after(function(){
    cy.task('installer:disable')
    cy.task('installer:revert_config')
  })

  function install_complete() {
    /*cy.wait(5000);
    cy.get('body').then(($body) => {
      if ($body.find('.login__title').length) {
        const header = 'login_header';
      } else {
        const header = 'header';
      }*/
      page.get('login_header').invoke('text').then((text) => {
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
    //})
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

})
