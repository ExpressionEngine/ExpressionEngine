
/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';
import Form from '../../elements/pages/installer/_install_form';

const page = new Installer
const install_form = new Form

context('Install with default theme', () => {
  before(function() {
    
  })

  beforeEach(function(){
    cy.task('installer:enable').then(() => {
      let installer_folder = '../../system/ee/installer';
      cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
        for (const item in files) {
          if (files[item].indexOf('system/ee/installer_') >= 0) {
            installer_folder = files[item];
            cy.task('filesystem:delete', '../../system/ee/installer').then(()=>{
              cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer'})
            })
          }
        }
      })
    })
    
    // Delete existing config and create a new one
    cy.task('db:clear')
    cy.task('cache:clear')

    cy.task('installer:create_config').then((path)=>{
      cy.log(path);
      page.load()
      //cy.screenshot({capture: 'runner'})
      //cy.screenshot({capture: 'fullPage'})
      cy.hasNoErrors()
  
      install_form.get('db_hostname').clear().type(Cypress.env("DB_HOST"))
      install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
      install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
      install_form.get('db_password').clear()
      if (Cypress.env("DB_PASSWORD") != '') {
          install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
      }
      install_form.get('install_default_theme').check()
      install_form.get('username').clear().type('admin')
      install_form.get('email_address').clear().type('hello@expressionengine.com')
      install_form.get('password').clear().type('1Password')
      install_form.get('license_agreement').click()
      install_form.get('install_submit').click()
  
      cy.hasNoErrors()
    })

  })

  afterEach(function(){
    let installer_folder = '../../system/ee/installer';
    cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
      for (const item in files) {
        if (files[item].indexOf('system/ee/installer_') >= 0) {
          installer_folder = files[item];
          cy.task('filesystem:delete', '../../system/ee/installer').then(()=>{
            cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer'})
          })
        }
      }
    })
  })

  after(function(){
    cy.task('installer:disable')
    cy.task('installer:revert_config').then(()=>{
        cy.task('installer:replace_config', {
            file: 'support/config/config.php', 
            options: {
                database: {
                    hostname: Cypress.env("DB_HOST"),
                    database: Cypress.env("DB_DATABASE"),
                    username: Cypress.env("DB_USER"),
                    password: Cypress.env("DB_PASSWORD")
                },
            },
        })
    })
  })

  it('pages in default theme have no errors', () => {
    cy.login({ email: 'admin', password: '1Password' });

    cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
    })

    cy.visit('index.php/')
    cy.hasNoErrors()
    cy.visit('/index.php/blog/entry/marrow-and-the-broken-bones')
    cy.hasNoErrors()
    cy.visit('/index.php/blog/entry/the-one-where-we-shake-it-ff')
    cy.hasNoErrors()
    cy.visit('/index.php/blog/entry/the-one-with-rope-cutting')
    cy.hasNoErrors()
    cy.visit('/index.php/member/1', {failOnStatusCode: false})
    cy.hasNoErrors()
    cy.visit('/index.php/blog')
    cy.hasNoErrors()
    cy.visit('/index.php/about')
    cy.hasNoErrors()
    cy.visit('/index.php/blog/category/news')
    cy.hasNoErrors()

  })

})
