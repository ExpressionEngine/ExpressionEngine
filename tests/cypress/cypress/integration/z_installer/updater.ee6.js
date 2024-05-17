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

context('Updater', () => {

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

  it('appears when using a database.php file', () => {
    cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(()=>{
      //cy.wait(5000);
      page.load()
      cy.get('body', { timeout: 20000 }).should('be.visible');
      cy.hasNoErrors()
      page.get('inline_errors').should('not.exist')
      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(/ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/)
      })
    })
  })

  it('shows an error when no database information exists at all', () => {
    //cy.wait(5000);
    cy.task('installer:delete_database_config').then(()=>{
      cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(()=>{
        //cy.wait(5000);
        page.load()
        cy.get('body', { timeout: 20000 }).should('be.visible');
        page.get('header').invoke('text').then((text) => {
          expect(text).to.eq('Install Failed')
        })
        page.get('error').contains('Unable to locate any database connection information.')
        cy.hasNoErrors()
      })
    })
  })

  it('turns system off if system was off before updating', () => {
      cy.task('installer:revert_config').then(() => {
          cy.task('installer:replace_config', {
              file: 'support/config/config-5.3.0.php', options: {
                  database: {
                      hostname: Cypress.env("DB_HOST"),
                      database: Cypress.env("DB_DATABASE"),
                      username: Cypress.env("DB_USER"),
                      password: Cypress.env("DB_PASSWORD")
                  },
                  app_version: '5.3.0',
                  is_system_on: 'n',
              }
          }).then(() => {
              cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(() => {
                  test_cli_update()
                  cy.task('installer:disable')
                  cy.eeConfig({ item: 'is_system_on' }).then((config) => {
                      expect(config.trim()).to.be.equal('n')
                  })
              })
          })
      })
  })

  it('turns system on if system was on before updating', () => {
      cy.task('installer:revert_config').then(() => {
          cy.task('installer:replace_config', {
              file: 'support/config/config-5.3.0.php', options: {
                  database: {
                      hostname: Cypress.env("DB_HOST"),
                      database: Cypress.env("DB_DATABASE"),
                      username: Cypress.env("DB_USER"),
                      password: Cypress.env("DB_PASSWORD")
                  },
                  app_version: '5.3.0',
                  is_system_on: 'y',
              }
          }).then(() => {
              cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(() => {
                  test_cli_update()
                  cy.task('installer:disable')
                  cy.eeConfig({ item: 'is_system_on' }).then((config) => {
                      expect(config.trim()).to.be.equal('y')
                  })
              })
          })
      })
  })

  it('updates a core installation successfully and installs the member module', () => {

    cy.task('installer:revert_config').then(()=>{
      cy.task('installer:replace_config', {
        file: 'support/config/config-3.0.5-core.php', options: {
          database: {
            hostname: Cypress.env("DB_HOST"),
            database: Cypress.env("DB_DATABASE"),
            username: Cypress.env("DB_USER"),
            password: Cypress.env("DB_PASSWORD")
          },
          app_version: '3.0.5'
        }
      }).then(()=>{
        cy.task('db:load', '../../support/sql/database_3.0.5-core.sql').then(()=>{
          from_version = '3.0.5'
          test_update()
          cy.task('db:query', 'SELECT count(*) AS count FROM exp_modules WHERE module_name = "Member"').then((result) => {
            expect(result[0].length).to.eq(1)
          })
        })
      })
    })
  })

  it('updates without notices going straight to login page', () => {
    cy.task('installer:revert_config').then(()=>{
      cy.task('installer:replace_config', {
        file: 'support/config/config-5.3.0.php', options: {
          database: {
            hostname: Cypress.env("DB_HOST"),
            database: Cypress.env("DB_DATABASE"),
            username: Cypress.env("DB_USER"),
            password: Cypress.env("DB_PASSWORD")
          },
          app_version: '5.3.0'
        }
      }).then(()=>{
        cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(()=>{
          from_version = '5.3.0'
          expect_login = true
          test_update(false, expect_login)
          page.get('success_actions').should('not.exist')

          //shows the banner about filemanager being in legacy mode
          cy.task('installer:disable').then(() => {
            cy.login();
            cy.get('.app-notice-file_manager_compatibility_mode').should('be.visible')
          })
        })
      })
    })
  })

  it('shows post-upgrade notice', () => {
    cy.task('installer:revert_config').then(()=>{
      cy.task('installer:replace_config', {
        file: 'support/config/config-5.3.0.php', options: {
          database: {
            hostname: Cypress.env("DB_HOST"),
            database: Cypress.env("DB_DATABASE"),
            username: Cypress.env("DB_USER"),
            password: Cypress.env("DB_PASSWORD")
          },
          app_version: '5.3.0'
        }
      }).then(()=>{
          cy.task('db:load', '../../support/sql/database_5.3.0.sql').then(()=>{
            from_version = '5.3.0'
            cy.task('db:query', "INSERT INTO `exp_fieldtypes` (`name`, `version`, `settings`, `has_global_settings`) VALUES ('fake_fieldtype','1.0.0','YTowOnt9','n');").then(()=>{
              cy.task('db:query', "INSERT INTO `exp_channel_fields` (`site_id`, `field_name`, `field_label`, `field_instructions`, `field_type`, `field_list_items`, `field_pre_populate`, `field_pre_channel_id`, `field_pre_field_id`, `field_ta_rows`, `field_maxl`, `field_required`, `field_text_direction`, `field_search`, `field_is_hidden`, `field_fmt`, `field_show_fmt`, `field_order`, `field_content_type`, `field_settings`, `legacy_field_data`) VALUES (1,'fake_fieldtype','fake_fieldtype','','fake_fieldtype','','n',0,0,10,0,'n','ltr','y','n','xhtml','y',2,'any','YTo2OntzOjE4OiJmaWVsZF9zaG93X3NtaWxleXMiO3M6MToieSI7czoxOToiZmllbGRfc2hvd19nbG9zc2FyeSI7czoxOiJ5IjtzOjIxOiJmaWVsZF9zaG93X3NwZWxsY2hlY2siO3M6MToieSI7czoyNjoiZmllbGRfc2hvd19mb3JtYXR0aW5nX2J0bnMiO3M6MToieSI7czoyNDoiZmllbGRfc2hvd19maWxlX3NlbGVjdG9yIjtzOjE6InkiO3M6MjA6ImZpZWxkX3Nob3dfd3JpdGVtb2RlIjtzOjE6InkiO30=','y');").then(()=>{
                test_update()
                page.get('success_actions').should('exist')
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
