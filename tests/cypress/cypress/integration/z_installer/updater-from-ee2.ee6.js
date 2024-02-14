/// <reference types="Cypress" />

import Updater from '../../elements/pages/installer/Updater';
import Form from '../../elements/pages/installer/_install_form';
import Success from '../../elements/pages/installer/_install_success';

const page = new Updater

const database = 'support/config/database-2.10.1.php'
const config = 'support/config/config-2.10.1.php'
let from_version = '2.10.1';
let expect_login = false;

// Note: Tests need `page.load()` to be called manually since we're manipulating
// files before testing the upgrade. Please do not add `page.load()` to any of the
// `before` calls.

context('Updater from EE2', () => {

  beforeEach(function(){

    cy.task('db:clear')
    cy.task('cache:clear')

    cy.task('installer:enable')
    cy.task('installer:test')
    cy.task('installer:replace_config', {file: config})
    cy.task('installer:replace_database_config', { file: database, options: {
        hostname: Cypress.env('DB_HOST'),
        database: Cypress.env('DB_DATABASE'),
        username: Cypress.env('DB_USER'),
        password: Cypress.env('DB_PASSWORD')
    }})

    // cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })

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
    })

    cy.task('filesystem:delete', '../../system/user/cache/mailing_list.zip')

  })

  afterEach(function(){
    cy.task('installer:revert_config')
    cy.task('installer:revert_database_config')
    cy.task('installer:backup_templates')
  })

  after(function(){
    cy.task('installer:restore_templates')
    cy.task('installer:disable')
    cy.task('installer:delete_database_config')
  })


  context('when updating from 2.x to 6.x', () => {
    beforeEach(function(){
      cy.task('db:load', '../../support/sql/database_2.10.1.sql')
    })

    it('updates using mysql as the dbdriver', () => {
      cy.task('installer:replace_database_config', {file: database, options: {dbdriver: 'mysql'}}).then(() => {
        test_update()
        test_templates()
      })
    })


    it('updates using localhost as the database host', () => {
      cy.task('installer:replace_database_config', {file: database, options: {hostname: 'localhost'}}).then(()=>{
        test_cli_update()
        test_templates()
      })
    })

    it('updates using 127.0.0.1 as the database host', () => {
      cy.task('installer:replace_database_config', {file: database, options: {hostname: '127.0.0.1'}}).then(()=>{
        test_cli_update()
        test_templates()
      })
    })

    it('updates with the old tmpl_file_basepath', () => {
      cy.task('installer:revert_config').then(()=>{
        cy.task('installer:replace_config', {
          file: config, options: {
                database: {
                    hostname: Cypress.env("DB_HOST"),
                    database: Cypress.env("DB_DATABASE"),
                    username: Cypress.env("DB_USER"),
                    password: Cypress.env("DB_PASSWORD")
                },
            tmpl_file_basepath: '../system/expressionengine/templates',
            app_version: '2.20.0'

          }
        }).then(()=>{
          test_cli_update()
          test_templates()
        })
      })
    })

    it('updates with invalid tmpl_file_basepath', () => {
      cy.task('installer:revert_config').then(()=>{
        cy.task('installer:replace_config', {
          file:config, options: {
            tmpl_file_basepath: '../system/not/a/directory/templates',
            app_version: '2.20.0'
          }
        }).then(()=>{
          test_cli_update()
          test_templates()
        })
      })
    })

    it('updates using new template basepath', () => {
      cy.task('installer:revert_config').then(()=>{
        cy.task('installer:replace_config', {
          file: config, options: {
            tmpl_file_basepath: '../system/user/templates',
            app_version: '2.20.0'
          }
        }).then(()=>{
          test_cli_update()
          test_templates()
        })
      })
    })

    it('has all required modules installed after the update', () => {
      test_update()
      test_templates()

      let installed_modules = []
      cy.task('db:query', 'SELECT module_name FROM exp_modules').then((result) => {
        result[0].forEach(function(row){
          installed_modules.push(row.module_name.toLowerCase());
        });

        expect(installed_modules).to.include('consent')
        expect(installed_modules).to.include('channel')
        expect(installed_modules).to.include('comment')
        expect(installed_modules).to.include('member')
        expect(installed_modules).to.include('stats')
        expect(installed_modules).to.include('rte')
        expect(installed_modules).to.include('file')
        expect(installed_modules).to.include('filepicker')
        expect(installed_modules).to.include('search')
        expect(installed_modules).to.include('pro')
      })

      cy.task('db:query', 'SELECT * FROM exp_dashboard_widgets').then((result) => {
        expect(result[0].length).to.be.gt(1)
      })
    })

    it('has all required modules installed after CLI update', () => {
      test_cli_update()
      test_templates()

      let installed_modules = []
      cy.task('db:query', 'SELECT module_name FROM exp_modules').then((result) => {
        result[0].forEach(function(row){
          installed_modules.push(row.module_name.toLowerCase());
        });

        expect(installed_modules).to.include('consent')
        expect(installed_modules).to.include('channel')
        expect(installed_modules).to.include('comment')
        expect(installed_modules).to.include('member')
        expect(installed_modules).to.include('stats')
        expect(installed_modules).to.include('rte')
        expect(installed_modules).to.include('file')
        expect(installed_modules).to.include('filepicker')
        expect(installed_modules).to.include('search')
        expect(installed_modules).to.include('pro')
      })

      cy.task('db:query', 'SELECT * FROM exp_dashboard_widgets').then((result) => {
        expect(result[0].length).to.be.gt(1)
      })
    })
  })

  it('updates and creates a mailing list export when updating from 2.x to 6.x with the mailing list module', () => {
    cy.task('db:load', '../../support/sql/database_2.10.1-mailinglist.sql').then(()=>{
      test_update(true)
    })
  })

  it('updates successfully when updating from 2.1.3 to 6.x', () => {
    cy.task('installer:revert_config').then(()=>{
      cy.task('installer:replace_config', {
        file: 'support/config/config-2.1.3.php', options: {
          app_version: '213'
        }
      }).then(()=>{
        cy.task('installer:revert_database_config').then(()=>{
          cy.task('installer:replace_database_config', {
            file: 'support/config/database-2.1.3.php'
          }).then(()=>{
            cy.task('db:load', '../../support/sql/database_2.1.3.sql').then(()=>{
              from_version = '2.1.3'
              test_cli_update()
            })
          })
        })
      })
    })
  })


  function test_cli_update(mailinglist = false) {
      // Delete any stored mailing lists
      cy.log('mailing list:')
      cy.log(mailinglist)

      const mailing_list_zip = '../../system/user/cache/mailing_list.zip'
      cy.task('filesystem:delete', mailing_list_zip).then(() => {
        cy.exec('php ../../system/ee/eecli.php update -v -y --skip-cleanup').then((result) => {
            expect(result.code).to.eq(0)
            expect(result.stderr).to.be.empty
            expect(result.stdout).to.not.contain('on line')
            expect(result.stdout).to.not.contain('caught:')
        })
      })

      test_version()
  }

  function test_update(mailinglist = false, expect_login = false) {
    // cy.log('wait 5 sec');
    // cy.wait(5000)

    // Delete any stored mailing lists
    cy.log('mailing list:')
    cy.log(mailinglist)

    const mailing_list_zip = '../../system/user/cache/mailing_list.zip'
    cy.task('filesystem:delete', mailing_list_zip).then(() => {

      //cy.wait(5000);
      page.load()
      cy.get('body', { timeout: 20000 }).should('be.visible');

      //cy.screenshot({capture: 'fullPage'})

      // Wait a second and try loading the page again in case we're not seeing the
      // correct page
      /*let header_step_1 = /ExpressionEngine to \d+\.\d+\.\d+/
      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(header_step_1)
      })*/

      page.get('inline_errors').should('not.exist')
      cy.hasNoErrors();
      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(/ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/)
      })

      page.get('submit').click()
      cy.hasNoErrors()

      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(/ExpressionEngine to \d+\.\d+\.\d+/)
      })
      page.get('updater_steps').contains('Running')

      if (mailinglist == true || expect_login == false) {
        cy.contains("Update Complete!", { matchCase: false, timeout: 10000 })
        // cy.get('body:contains("Update Complete!")').contains("Update Complete!", { matchCase: false, timeout: 200000 })
      } else {
        cy.contains("Log into", { matchCase: false, timeout: 10000 })
        // cy.get('body:contains("Log into")').contains("Log into", { matchCase: false, timeout: 200000 })
      }

      cy.hasNoErrors()

      cy.get('body').then(($body) => {
        if ($body.find('.login__title').length) {
          cy.contains('Username');
          cy.contains('Password');
          cy.contains('Remind me');

          cy.get('input[type=submit]').should('not.be.disabled');
        } else {
          page.get('success_actions').should('exist')
          page.get('success_actions').first().invoke('text').then((text) => {
            expect(text).to.eq('Log In')
          })
        }
      })

      cy.log('Update Complete!');

      page.get('error').should('not.exist')

      if (mailinglist == true) {
        page.get('success_actions').its('length').should('eq', 2)
        page.get('success_actions').last().invoke('text').then((text) => {
          expect(text).to.eq('Download Mailing List')
        })
        cy.task('filesystem:exists', mailing_list_zip).then((exists) => {
          expect(exists).to.eq(true)
        })
      }

      let installer_folder = '../../system/ee/installer';
      cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
        for (const item in files) {
          if (files[item].indexOf('system/ee/installer') >= 0) {
            installer_folder = files[item];
            cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer'})
          }
        }
      })

      test_version()
    })
  }

  function test_version() {
    cy.task('filesystem:read', '../../system/user/config/config.php').then((config) => {
      let config_version = config.match(/\$config\['app_version'\]\s+=\s+["'](.*?)["'];/)[1]
      cy.task('filesystem:read', '../../system/ee/installer/controllers/wizard.php').then((wizard) => {
        let wizard_version = wizard.match(/public \$version\s+=\s+["'](.*?)["'];/)[1]

        // @TODO UD files don't account for -dp.#, so just compare the first three segs
        let conf = config_version.split(/[\.\-]/)
        let wiz = wizard_version.split(/[\.\-]/)

        expect(conf[0]).to.eq(wiz[0])
        expect(conf[1]).to.eq(wiz[1])
        expect(conf[2]).to.eq(wiz[2])
      })
    })
  }

  function test_templates() {
    cy.task('filesystem:exists', '../../system/user/templates/default_site/').then((exists) => {
      expect(exists).to.eq(true)
    })

    // Ensure none of the templates say anything about Directory access being
    // forbidden
    cy.task('filesystem:list', {target: '../../system/user/templates/default_site/', mask: '**/*.html'}).then((files) => {
      for (const key in files) {
        cy.log(files[key]);
        cy.task('filesystem:read', files[key]).then((file) => {
          expect(file).to.not.include('Directory access is forbidden.')
        })
      }
    })
  }

})
