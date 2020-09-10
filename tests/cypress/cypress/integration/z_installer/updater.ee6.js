/// <reference types="Cypress" />

import Updater from '../../elements/pages/installer/Updater';
import Form from '../../elements/pages/installer/_install_form';
import Success from '../../elements/pages/installer/_install_success';

const page = new Updater

const database = 'support/config/database-2.10.1.php'
const config = 'support/config/config-2.10.1.php'

// Note: Tests need `page.load()` to be called manually since we're manipulating
// files before testing the upgrade. Please do not add `page.load()` to any of the
// `before` calls.

context('Updater', () => {

  beforeEach(function(){

    cy.task('db:clear')
    cy.task('db:load', '../../support/sql/database_2.10.1.sql')

    cy.task('installer:enable')
    cy.task('installer:replace_config', {file: config})
    cy.task('installer:replace_database_config', {file: database})

    let installer_folder = '../../system/ee/installer';
    cy.task('filesystem:list', {target: '../../system/ee/'}).then((files) => {
      for (const item in files) {
        if (files[item].indexOf('system/ee/installer') >= 0) {
          installer_folder = files[item];
          cy.task('filesystem:rename', {from: installer_folder, to: '../../system/ee/installer'})
        }
      }
    })

    //@version = '2.20.0'
    //@installer.version = @version

    cy.hasNoErrors()
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
    page.load()
    page.get('inline_errors').should('not.exist')
    page.get('header').invoke('text').then((text) => {
      expect(text).to.match(/ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/)
    })
  })

  it('shows an error when no database information exists at all', () => {
    cy.task('installer:delete_database_config').then(()=>{
      page.load()
      page.get('header').invoke('text').then((text) => {
        expect(text).to.eq('Install Failed')
      })
      page.get('error').contains('Unable to locate any database connection information.')
    })
  })

  context('when updating from 2.x to 3.x', () => {
    it.only('updates using mysql as the dbdriver', () => {
      cy.task('installer:replace_database_config', {file: database, options: {dbdriver: 'mysql'}}).then(() => {
        test_update()
        test_templates()
      })
    })
  })
/*
    it('updates using localhost as the database host', () => {
      @installer.replace_database_config(@database, hostname: 'localhost')
      test_update()
      test_templates()
    }

    it('updates using 127.0.0.1 as the database host', () => {
      @installer.replace_database_config(@database, hostname: '127.0.0.1')
      test_update()
      test_templates()
    }

    it('updates with the old tmpl_file_basepath', () => {
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/expressionengine/templates',
        app_version: '2.20.0'
      )
      test_update()
      test_templates()
    }

    it('updates with invalid tmpl_file_basepath', () => {
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/not/a/directory/templates',
        app_version: '2.20.0'
      )
      test_update()
      test_templates()
    }

    it('updates using new template basepath', () => {
      @installer.revert_config
      @installer.replace_config(
        @config,
        tmpl_file_basepath: '../system/user/templates',
        app_version: '2.20.0'
      )
      test_update()
      test_templates()
    }

    it('has all required modules installed after the update', () => {
      test_update()
      test_templates()

      installed_modules = []
      $db.query('SELECT module_name FROM exp_modules').each do |row|
        installed_modules << row['module_name'].downcase
      }

      installed_modules.should include('channel')
      installed_modules.should include('comment')
      installed_modules.should include('member')
      installed_modules.should include('stats')
      installed_modules.should include('rte')
      installed_modules.should include('file')
      installed_modules.should include('filepicker')
      installed_modules.should include('search')
    }
  }

  it('updates and creates a mailing list export when updating from 2.x to 3.x with the mailing list module', () => {
    clean_db do
      $db.query(IO.read('sql/database_2.10.1-mailinglist.sql'))
      clear_db_result
    }

    test_update()(true)
  }

  it('updates successfully when updating from 2.1.3 to 3.x', () => {
    @installer.revert_config
    @installer.replace_config(
      File.expand_path('../circleci/config-2.1.3.php'),
      app_version: '213'
    )
    @installer.revert_database_config
    @installer.replace_database_config(
      File.expand_path('../circleci/database-2.1.3.php')
    )

    clean_db do
      $db.query(IO.read('sql/database_2.1.3.sql'))
      clear_db_result
    }

    test_update()
  }

  it('updates a core installation successfully and installs the member module', () => {
    @installer.revert_config
    @installer.replace_config(
      File.expand_path('../circleci/config-3.0.5-core.php'),
      database: {
        hostname: $test_config[:db_host],
        database: $test_config[:db_name],
        username: $test_config[:db_username],
        password: $test_config[:db_password]
      },
      app_version: '3.0.5'
    )

    clean_db do
      $db.query(IO.read('sql/database_3.0.5-core.sql'))
      clear_db_result
    }

    test_update()

    $db.query('SELECT count(*) AS count FROM exp_modules WHERE module_name = "Member"').each do |row|
      row['count'].should == 1
    }
  }
*/
  function test_update(mailinglist = false) {
    // Delete any stored mailing lists
    const mailing_list_zip = '../../system/user/cache/mailing_list.zip'
    cy.task('filesystem:delete', mailing_list_zip).then(() => {

      page.load()

      // Wait a second and try loading the page again in case we're not seeing the
      // correct page
      /*let header_step_1 = /ExpressionEngine to \d+\.\d+\.\d+/
      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(header_step_1)
      })*/

      page.get('inline_errors').should('not.exist')
      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(/ExpressionEngine from \d+\.\d+\.\d+ to \d+\.\d+\.\d+/)
      })

      page.get('submit').click()
      cy.hasNoErrors()

      page.get('header').invoke('text').then((text) => {
        expect(text).to.match(/ExpressionEngine to \d+\.\d+\.\d+/)
      })
      page.get('updater_steps').contains('Running')

      cy.hasNoErrors()

      cy.get('h1:contains("Log into")').contains("Log into", { matchCase: false, timeout: 200000 })

      cy.hasNoErrors()

      page.get('header').invoke('text').then((text) => {
        if (text == "Update Complete!") {
          page.get('success_actions').should('exist')
          page.get('success_actions').first().invoke('text').then((text) => {
            expect(text).to.eq('Log In')
          })
        } else {
          cy.contains('Username');
          cy.contains('Password');
          cy.contains('Remind me');

          cy.get('input[type=submit]').should('not.be.disabled');
        }
      })

      if (mailinglist == false) {
        //page.get('success_actions').its('length').should('eq', 1)
      } else {
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
