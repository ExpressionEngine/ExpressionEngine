
/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';
import Form from '../../elements/pages/installer/_install_form';

const page = new Installer
const install_form = new Form

context('Install with default theme', () => {

  before(function() {
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

  after(function(){
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

  context('pages in default theme have no errors', () => {

    before(() => {
      cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
      cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })
      cy.login({ email: 'admin', password: '1Password' });

      cy.visit('admin.php?/cp/design/manager/layouts');
      cy.get('a:contains("_html-wrapper")').click()
      cy.get('.CodeMirror-code').type('{home}{pageup}{uparrow}{shift}{end}{del}', {release: false})
      cy.get('.CodeMirror-code').type('{home}{pageup}{{}layout="cypress/layout"}')
      cy.get('body').type('{ctrl}', {release: false}).type('s')
    })

    it('file manager is not in compatibility mode', () => {
      cy.eeConfig({ item: 'file_manager_compatibility_mode' }).then((config) => {
        expect(config).not.eq('y')
      })
    })

    it('there are no missing files in filemanager', () => {
      cy.visit('admin.php?/cp/files')
      cy.wait(2000)
      cy.login({ email: 'admin', password: '1Password' });
      cy.get('.ee-wrapper').should('exist')
      cy.get('.app-notice-missing-files').should('not.exist')
      cy.hasNoErrors()
    })

    it('homepage', () => {

      cy.on('uncaught:exception', (err, runnable) => {
          // return false to prevent the error from
          // failing this test
          return false
      })

      cy.visit('index.php/')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('Entry with BandCamp audio', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/blog/entry/marrow-and-the-broken-bones')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('Entry with SoundCloud audio', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/blog/entry/the-one-where-we-shake-it-ff')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('Entry with YouTube video', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/blog/entry/the-one-with-rope-cutting')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('member', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/member/1', {failOnStatusCode: false})
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('blog', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/blog')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('about', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/about')
      cy.hasNoErrors()
      cy.logFrontendPerformance()
    })

    it('category', () => {

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })
      
      cy.visit('/index.php/blog/category/news')
      cy.hasNoErrors()
      cy.logFrontendPerformance()

    })

  })

  context('adding the files to entries', () => {
    before(() => {
      cy.visit('admin.php?/cp/fields')
      cy.wait(2000)
      cy.login({ email: 'admin', password: '1Password' });
      cy.get('.ee-wrapper').should('exist')
      cy.get('.list-item__content').contains('Page Content').click()
      cy.get('input[type=checkbox][name=field_show_formatting_btns]:visible').check()
      cy.get('input[type=checkbox][name=field_show_file_selector]:visible').check()
      cy.get('body').type('{ctrl}', {release: false}).type('s')
    })

    it('add files and display them on frontend', () => {
      cy.intercept("**/filepicker/**").as('ajax')
      cy.login({ email: 'admin', password: '1Password' });
      cy.visit('/admin.php?/cp/publish/edit/entry/1')
      cy.wait(2000) //wait for picker on textarea to initiliaze
      cy.get('.textarea-field-filepicker').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.get('.modal-file .app-listing__row a').contains('lake.jpg').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:3:url}"')
      })

      cy.get('.grid-field__table tr:visible .file-field-filepicker[title=Edit]').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.get('.modal-file .app-listing__row a').contains('ocean.jpg').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.get('.grid-field__table tr:visible .js-file-input').invoke('val').then((val) => {
        expect(val).to.eq('{file:4:url}')
      })
      cy.get('.grid-field__table tr:visible .fields-upload-chosen-name').should('contain', 'ocean.jpg')

      cy.get('body').type('{ctrl}', {release: false}).type('s')
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:3:url}"')
      })
      cy.get('.grid-field__table tr:visible .fields-upload-chosen-name').should('contain', 'ocean.jpg')

      cy.on('uncaught:exception', (err, runnable) => {
        // return false to prevent the error from
        // failing this test
        return false
      })

      cy.visit('/index.php/about')
      cy.hasNoErrors()
      cy.get('figure.right img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('figure.right img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('ocean.jpg')
      })
      cy.get('section.w-12 p img').should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('lake.jpg')
      })

      //turn on compatibility mode and make sure everything still works
      cy.log('turn on compatibility mode and make sure everything still works')
      cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'y' })
      cy.wait(1000)
      
      cy.visit('/index.php/about')
      cy.hasNoErrors()
      cy.get('figure.right img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('figure.right img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('ocean.jpg')
      })
      cy.get('section.w-12 p img').should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('lake.jpg')
      })

      //add / replace images in compatibility mode
      cy.log('add / replace images in compatibility mode')
      cy.authVisit('/admin.php?/cp/publish/edit/entry/1')
      cy.wait(2000) //wait for picker on textarea to initiliaze
      cy.get('.textarea-field-filepicker').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.get('.modal-file .app-listing__row a').contains('path.jpg').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:3:url}"')//still there
      })
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{filedir_6}path.jpg"')
      })

      cy.get('.grid-field__table tr:visible .file-field-filepicker[title=Edit]').click()
      cy.wait('@ajax')
      cy.get('.modal-file').should('be.visible')
      cy.get('.modal-file .app-listing__row a').contains('sky.jpg').click()
      cy.get('.modal-file').should('not.be.visible')
      cy.get('.grid-field__table tr:visible .js-file-input').invoke('val').then((val) => {
        expect(val).to.eq('{filedir_6}sky.jpg')
      })
      cy.get('.grid-field__table tr:visible .fields-upload-chosen-name').should('contain', 'sky.jpg')

      cy.get('body').type('{ctrl}', {release: false}).type('s')
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{file:3:url}"')
      })
      cy.get('textarea.markItUpEditor').invoke('val').then((val) => {
        expect(val).to.contain('<img src="{filedir_6}path.jpg"')
      })
      cy.get('.grid-field__table tr:visible .fields-upload-chosen-name').should('contain', 'sky.jpg')

      cy.visit('/index.php/about')
      cy.hasNoErrors()
      cy.get('figure.right img').should('be.visible').and(($img) => {
            // "naturalWidth" and "naturalHeight" are set when the image loads
            expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('figure.right img').invoke('attr', 'src').then((src) => {
        expect(src).to.contain('sky.jpg')
      })
      cy.get('section.w-12 p img').eq(0).should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').eq(0).invoke('attr', 'src').then((src) => {
        expect(src).to.contain('lake.jpg')
      })
      cy.get('section.w-12 p img').eq(1).should('be.visible').and(($img) => {
        // "naturalWidth" and "naturalHeight" are set when the image loads
        expect($img[0].naturalWidth).to.be.greaterThan(0)
      })
      cy.get('section.w-12 p img').eq(1).invoke('attr', 'src').then((src) => {
        expect(src).to.contain('path.jpg')
      })

      cy.eeConfig({ item: 'file_manager_compatibility_mode', value: 'n' })
    })

  })

})
