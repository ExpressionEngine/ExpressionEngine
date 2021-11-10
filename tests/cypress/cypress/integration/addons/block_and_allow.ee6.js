/// <reference types="Cypress" />

import BlockAndAllow from '../../elements/pages/addons/BlockAndAllow';
import AddonManager from '../../elements/pages/addons/AddonManager';

const page = new BlockAndAllow
const addon_manager = new AddonManager;

context('Block and Allow', () => {

  before(function(){
    cy.task('db:seed')
    cy.intercept('**/check').as('check')
    cy.intercept('**/license/handleAccessResponse').as('license')

    cy.auth();

    // Install Pages
    addon_manager.load()
    cy.hasNoErrors()
    addon_manager.get('first_party_addons').find('.add-on-card:contains("Block and Allow") a').click()

    cy.wait('@check')
    cy.wait('@license')
  })

  beforeEach(function() {

    cy.authVisit(page.url);

    page.get('page_title').contains('Block and Allow')

    cy.hasNoErrors()

    for (const el in page.all_there) {
        cy.get(page.all_there[el]).should('exist')
    }

  })

  it('should save Blocked', () => {
    page.get('blockedlist_ip').should('be.visible')
    page.get('allowedlist_ip').should('not.be.visible')
    page.get('htaccess_path').should('not.be.visible')

    page.get('blockedlist_ip').type('1.1.1.1');

    cy.get('.button--primary:visible').first().click()

    page.get('wrap').contains('updated successfully');

    page.get('blockedlist_ip').contains('1.1.1.1');
  })

  it('should download blocked list', () => {
    page.get('blockedlist_ip').should('be.visible')
    page.get('allowedlist_ip').should('not.be.visible')
    page.get('htaccess_path').should('not.be.visible')

    cy.get('.button--secondary:visible').first().click()

    page.get('wrap').contains('The blocked list has been updated by being downloaded');

    page.get('blockedlist_ip').contains('1.1.1.1');
    page.get('blockedlist_ip').contains('90.200.204.203');

    page.get('blockedlist_agent').contains('AdultGods');

    page.get('blockedlist_url').contains('-poker.');
  })

  it('should save Allowed', () => {
    page.get('allowed_tab_switch').click()
    
    page.get('blockedlist_ip').should('not.be.visible')
    page.get('allowedlist_ip').should('be.visible')
    page.get('htaccess_path').should('not.be.visible')

    page.get('allowedlist_url').type('expressionengine.com');

    cy.get('.button--primary:visible').first().click()

    page.get('wrap').contains('updated successfully');

    page.get('allowed_tab_switch').click()
    page.get('allowedlist_url').contains('expressionengine.com');
  })

  it('should download allowed list', () => {
    page.get('allowed_tab_switch').click()
    
    page.get('blockedlist_ip').should('not.be.visible')
    page.get('allowedlist_ip').should('be.visible')
    page.get('htaccess_path').should('not.be.visible')

    cy.get('.button--secondary:visible').first().click()

    page.get('wrap').contains('The allowed list has been updated by being downloaded');

    page.get('allowed_tab_switch').click()

    page.get('allowedlist_url').contains('expressionengine.com');
    page.get('allowedlist_url').contains('del.icio.us');
  })

  it('should save to .htaccess', () => {
    page.get('settings_tab_switch').click()
    
    page.get('blockedlist_ip').should('not.be.visible')
    page.get('allowedlist_ip').should('not.be.visible')
    page.get('htaccess_path').should('be.visible')

    cy.task('filesystem:createFile', Cypress.env("TEMP_DIR")+'/.htaccess').then(()=>{
        page.get('htaccess_path').clear().type(Cypress.env("TEMP_DIR")+'/.htaccess');
    })

    cy.get('.button--primary:visible').first().click()

    page.get('settings_tab_switch').click()

    page.get('wrap').contains('The .htaccess file was written successfully');

    cy.task('filesystem:read', Cypress.env("TEMP_DIR")+'/.htaccess').then((htaccess) => {
        expect(htaccess).to.contain('##EE Spam Block')
        expect(htaccess).to.contain('SetEnvIfNoCase Referer "^$" GoodHost')
        expect(htaccess).to.contain('porn')
    })
  })


})
