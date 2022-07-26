/// <reference types="Cypress" />

import Installer from '../../elements/pages/installer/Installer';

const page = new Installer

context('One-Click Updater', () => {

  var latestVersion = '';
 
  before(function(){
    cy.task('updater:backup_files')
    cy.task('db:seed')
    cy.task('installer:disable')

    cy.eeConfig({item: 'app_version'}).then((app_version) => {
      latestVersion = app_version;
      // This test is also used in the pre-release.yml workflow and gets a copy of 6.1.5
      // We've just selected the same version here to not interfere with that test
      // but also allow this to do a simple check for working updater in current code
      cy.eeConfig({item: 'app_version', value: '6.1.5'})
    })
  })

  beforeEach(function() {

    /*system = '../../system/'
    @config_path = File.expand_path('user/config/config.php', system)
    @syspath = File.expand_path('ee/', system);
    @themespath = File.expand_path('../../themes/ee/');*/

    cy.auth();
    cy.get('.ee-sidebar__version').should('be.visible')

    cy.task('filesystem:delete', '../../system/user/cache/current_version')
    cy.wait(5000)

    cy.visit('admin.php')

    cy.get('.ee-sidebar__version').click();
    cy.get('.app-about__status .button--primary').should('be.visible');
    //app-about__status-version 6.1.6

  })

  afterEach(function() {
    // Expand stack trace if we have one
    //click_link('view stack trace') unless page.has_no_css?('a[rel="updater-stack-trace"]')
  })

  after(function() {
      cy.task('updater:restore_files')
  })

  it('should fail preflight check when permissions are incorrect', () => {
    cy.exec(`chmod 444 '../../system/user/config/config.php'`)
    cy.get('.app-about__status .button--primary:visible').click()
    if (Cypress.platform === 'win32')
    {
        cy.log('skipped because of Windows platform')
    } else {

      cy.get('body').contains('Update Stopped')
      cy.get('body').contains('The following paths are not writable:')

      cy.exec(`chmod 666 '../../system/user/config/config.php'`)

      cy.get('a:contains("Continue")').click()

      cy.intercept("POST", "**C=updater&M=run&step=selfDestruct").as("selfDestruct");
      cy.wait('@selfDestruct');
      cy.visit('admin.php')
      cy.get('body').contains('Up to date!')

      cy.get('.ee-sidebar__version-number').invoke('text').then((text) => {
        expect(text).to.eq(latestVersion)
      })
    }
  })

  it.skip('should continue update when permissions are fixed', () => {
    cy.screenshot({capture: 'fullPage'});
    page.get('wrap').contains('Update Stopped')

    if (Cypress.platform === 'win32')
    {
        cy.log('skipped because of Windows platform')
    } else {
        cy.exec(`chmod 666 '../../system/user/config/config.php'`)
        cy.hasNoErrors()

        cy.get('a:contains("view stack trace")').click()
        page.hasAlert('error')
        cy.get('body').contains("File Not Writable")
        cy.get('body').contains("Cannot write to the file")
        cy.get('body').contains("Check your file permissions on the server")
    }
    /*File.chmod(0777, @syspath)
    FileUtils.chmod(0777, Dir.glob(@syspath+'/*'))
    File.chmod(0777, @themespath)
    FileUtils.chmod(0777, Dir.glob(@themespath+'/*'))*/

    cy.get('a:contains("Continue")').click()

    cy.get('body').contains('Up to date!')
  })

  it.skip('should update if there are no impediments', () => {
    page.find('.app-about__version').click()
    page.find('.app-about-info__status--update .button').click()

    cy.get('body').contains('Up to date!')
  })

})
