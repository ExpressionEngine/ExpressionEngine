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

    // Preserve a regex compiled by an older release, including its legacy capture names.
    // The update must rebuild it before the new parser reads the named segments.
    cy.eeConfig({ item: 'enable_template_routes', value: 'y' })
    cy.eeConfig({ item: 'save_tmpl_files', value: 'n' })
    cy.task('db:query', "SELECT t.template_id FROM exp_templates t JOIN exp_template_groups g ON g.group_id = t.group_id WHERE g.group_name = 'about' AND t.template_name = 'index'")
      .then(([templates]) => {
        expect(templates).to.have.length(1)
        const templateId = templates[0].template_id
        cy.task('db:query', {
          sql: 'UPDATE exp_templates SET template_data = ?, cache = ? WHERE template_id = ?',
          values: ['category={segment:category};entry={segment:entry}', 'n', templateId]
        })
        cy.task('db:query', { sql: 'DELETE FROM exp_template_routes WHERE template_id = ?', values: [templateId] })
        cy.task('db:query', {
          sql: 'INSERT INTO exp_template_routes (template_id, route, route_parsed, route_required, `order`) VALUES (?, ?, ?, ?, ?)',
          values: [templateId, '/compat-upgrade/{category:alpha_dash}/{entry:alpha_dash}', "^(compat-upgrade)\\/((?P<eau88zztlnqos048sc80go4g4skgo0gg>(((?=\\b([A-Za-z0-9_-]+)\\b)([^\\/]*)))))\\/((?P<ebf4igd1hpwoogk4sgkkswk0s444so8s>(((?=\\b([A-Za-z0-9_-]+)\\b)([^\\/]*)))))\\/?$", 'y', 0]
        })
      })

    // Orphaned rows are ignored by routing and must not prevent an otherwise valid upgrade.
    cy.task('db:query', {
      sql: 'INSERT INTO exp_template_routes (template_id, route, route_parsed, route_required, `order`) VALUES (?, ?, ?, ?, ?)',
      values: [0, '/unused/{', '^unused$', 'y', 0]
    })
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

    cy.task('db:query', 'SELECT route, route_parsed FROM exp_template_routes WHERE template_id = 0')
      .then(([routes]) => {
        expect(routes).to.deep.equal([{ route: '/unused/{', route_parsed: '^unused$' }])
      })

    // Check the public response without the logged-in Pro editing markup.
    cy.clearCookies()
    cy.request('/index.php/compat-upgrade/news/example').then((response) => {
      expect(response.status).to.equal(200)
      expect(response.body.trim()).to.equal('category=news;entry=example')
    })
  })

})
