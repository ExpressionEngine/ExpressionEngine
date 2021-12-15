
/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';
import Form from '../../elements/pages/installer/_install_form';
import Success from '../../elements/pages/installer/_install_success';

const page = new Installer
const install_form = new Form
const install_success = new Success

context('Install with default theme', () => {
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
        //cy.screenshot({capture: 'runner'})
        //cy.screenshot({capture: 'fullPage'})
        cy.hasNoErrors()

        install_form.get('db_hostname').clear().type('localhost')
        install_form.get('db_name').clear().type(Cypress.env("DB_DATABASE"))
        install_form.get('db_username').clear().type(Cypress.env("DB_USER"))
        install_form.get('db_password').clear()
        if (Cypress.env("DB_PASSWORD") != '') {
            install_form.get('db_password').type(Cypress.env("DB_PASSWORD"))
        }
        install_form.get('install_default_theme').check()
        install_form.get('username').clear().type('admin')
        install_form.get('email_address').clear().type('hello@expressionengine.com')
        install_form.get('password').clear().type('password')
        install_form.get('license_agreement').click()
        install_form.get('install_submit').click()

        cy.hasNoErrors()
      })
    })
  })

  after(function(){
    cy.task('installer:disable')
    cy.task('installer:revert_config')
  })

  it('pages in default theme have no errors', () => {
    cy.login()

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
    cy.visit('/index.php/member/1')
    cy.hasNoErrors()
    cy.visit('/index.php/blog')
    cy.hasNoErrors()
    cy.visit('/index.php/about')
    cy.hasNoErrors()
    cy.visit('/index.php/blog/category/news')
    cy.hasNoErrors()

  })

})
