/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';

const addon_manager = new AddonManager;
const siteManager = new SiteManager;
const form = new SiteForm;

context('Query add-on', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })
    cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
    cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' })
    cy.auth();
    addon_manager.load()
    addon_manager.get('first_party_addons').find('.add-on-card:contains("Query") a').click()
    cy.authVisit('admin.php?/cp/design')
    siteManager.load();
  })

  context('Select with exp:query tag', function(){
    beforeEach(function() {
      cy.authVisit('index.php/query/index')
    })

    it('File path are not parsed by default', function(){
        cy.get('.query-results__field_id_3').first().invoke('text').should('eq', "{filedir_2}ee_banner_120_240.gif")
    })
    it('File path is parsed when parameter passed', function(){
        cy.get('.query-results--parsed-files__field_id_3').first().invoke('text').should('eq', "/images/about/ee_banner_120_240.gif")
    })
    it('{base} variables are parsed', function(){
      var baseUrl = Cypress.config().baseUrl.endsWith('/') ? Cypress.config().baseUrl.slice(0, -1) : Cypress.config().baseUrl
      cy.log(baseUrl)
      cy.get('.query-results--parsed-bases__3 .query-results--parsed-bases__url').first().invoke('text').should('contain', baseUrl + "/images/avatars/")
      cy.get('.query-results--parsed-bases__3').first().should('have.class', 'odd')
    })
  })
})
