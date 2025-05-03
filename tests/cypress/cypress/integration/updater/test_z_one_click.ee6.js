/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';

const page = new Installer

var app_version;

context('One-Click Updater', () => {
 
  before(function(){
    cy.task('updater:backup_files')
    cy.task('db:seed')
    cy.task('installer:disable')
    cy.exec(`chmod 666 '../../system/user/config/config.php'`)

    cy.eeConfig({ item: 'app_version' }).then((config) => {
      app_version = config
      cy.log('Current version: ' + app_version)
    })

    // This test is also used in the pre-release.yml workflow and gets a copy of 6.1.5
    // We've just selected the same version here to not interfere with that test
    // but also allow this to do a simple check for working updater in current code
    cy.eeConfig({item: 'app_version', value: '7.0.0'})
  })

  beforeEach(function() {

    cy.auth();
    cy.get('.ee-sidebar__version').should('be.visible')

    cy.task('filesystem:delete', '../../system/user/cache/current_version')
    cy.wait(5000)

    cy.visit('admin.php')

    cy.get('.ee-sidebar__version').click();
    cy.get('.app-about__status .button--primary').should('be.visible');
    
  })

  afterEach(function() {
    // Expand stack trace if we have one
    //click_link('view stack trace') unless page.has_no_css?('a[rel="updater-stack-trace"]')
  })

  after(function() {
      cy.task('updater:restore_files')
  })

  it('Fail preflight check when file permissions are incorrect, updates when fixed', () => {
    cy.exec(`chmod 444 '../../system/user/config/config.php'`)
    cy.get('.app-about__status .button--primary:visible').click()

    cy.get('body').contains('Update Stopped')
    cy.get('body').contains('The following paths are not writable:')

    cy.exec(`chmod 666 '../../system/user/config/config.php'`)

    cy.get('a:contains("Continue")').click()

    cy.intercept("POST", "**C=updater&M=run&step=download").as("download");
    cy.wait('@download', {timeout: 200000});

    cy.intercept("POST", "**C=updater&M=run&step=selfDestruct").as("selfDestruct");
    cy.wait('@selfDestruct', {timeout: 200000});
    cy.visit('admin.php')
    cy.get('body').contains('Up to date!')

      cy.get('.ee-sidebar__version-number').invoke('text').then((text) => {
        expect(text).to.contain(app_version)
      })
      cy.eeConfig({ item: 'app_version' }).then((config) => {
        expect(config).to.contain(app_version)
      })
  })

})
