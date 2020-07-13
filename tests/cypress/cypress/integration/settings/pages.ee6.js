/// <reference types="Cypress" />

import PagesSettings from '../../elements/pages/settings/PagesSettings';
import AddonManager from '../../elements/pages/addons/AddonManager';

const page = new PagesSettings
const addon_manager = new AddonManager;

context('Pages Settings', () => {

  before(function(){
    cy.task('db:seed')

    cy.auth();

    page.get('settings_btn').click()
    page.get('wrap').invoke('text').then((text) => {
      expect(text).not.contains( 'Pages Settings')
    })

    // Install Pages
    addon_manager.load()
    cy.hasNoErrors()
    addon_manager.get('first_party_addons').find('.add-on-card:contains("Pages") a').click()
  })

  beforeEach(function() {

    cy.auth();
    page.load()
    cy.hasNoErrors()
  })

  it('should show the Pages Settings screen', () => {
    page.get('homepage_display').should('exist')
    page.get('default_channel').should('exist')
    page.get('channel_default_template').should('exist')

    //page.all_there?.should == true

    page.get('homepage_display').filter('[value=not_nested]').should('be.checked')

    page.get('default_channel').filter('[value=0]').should('be.checked')
    page.get('channel_default_template').eq(0).invoke('val').then((val) => { expect(val).to.be.equal('0') })
    page.get('channel_default_template').eq(1).invoke('val').then((val) => { expect(val).to.be.equal('0') })
  })

  it('should save new Pages settings', () => {
    page.get('homepage_display').filter('[value=nested]').check()
    page.get('default_channel').filter('[value=1]').check()
    page.get('channel_default_template').eq(0).select('about/404')
    page.get('channel_default_template').eq(1).select('news/index')
    //page.submit()
    cy.get('button').contains('Save Settings').first().click()

    cy.hasNoErrors()
    page.get('wrap').contains('Preferences updated')
    page.get('homepage_display').filter('[value=nested]').should('be.checked')
    page.get('default_channel').filter('[value=1]').should('be.checked')
    page.get('channel_default_template').eq(0).invoke('val').then((val) => { expect(val).to.be.equal('2') })
    page.get('channel_default_template').eq(1).invoke('val').then((val) => { expect(val).to.be.equal('10') })
  })
})
