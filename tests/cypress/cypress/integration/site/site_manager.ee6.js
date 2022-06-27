/// <reference types="Cypress" />

import SiteManager from '../../elements/pages/site/SiteManager';
import SiteForm from '../../elements/pages/site/SiteForm';

const page = new SiteManager;

let counter = 1;

context('Site Manager', () => {

  before(function(){
    cy.task('db:seed')
    cy.eeConfig({item: 'multiple_sites_enabled', value: 'y'})
    cy.wait(5000)
    cy.eeConfig({item: 'multiple_sites_enabled'}) .then((config) => {
      expect(config.trim()).to.be.equal('y')
    })
  })

  beforeEach(function() {
    cy.authVisit('admin.php?/cp/msm');
    cy.hasNoErrors()
  })

  /*it('displays', () => {
    page.all_there?.should('eq', true
  })*/

  context('with multiple sites', () => {
    beforeEach(function() {

      counter++;

      //page.get('add_site_button').click()//AJ
      cy.dismissLicenseAlert()
      cy.get('.main-nav a').contains('Add Site').first().click()

      cy.hasNoErrors()

      const form = new SiteForm
      form.add_site({
        name: 'Rspec Site ' + counter,
        short_name: 'rspec_site_' + counter,
      })

      cy.hasNoErrors()

      page.get('alert').should('exist')
      page.get('alert').contains('Site Created')
      page.get('alert').contains('Rspec Site ' + counter)


    })

    it('can add a site', () => {
      page.get('sites').should('have.length', 2)
      page.get('sites').eq(1).find('td:first-child').contains('2')
      page.get('sites').eq(1).find('td:nth-child(2)').contains('Rspec Site ' + counter)
      page.get('sites').eq(1).find('td:nth-child(3)').contains('{rspec_site_'+counter+'}')
      page.get('sites').eq(1).find('td:nth-child(4)').contains('Online')
    })

    it('can delete a site', () => {
      page.get('sites').eq(1).find('td:nth-child(5) input').click()

      page.get('bulk_action').should('exist')
      page.get('action_submit_button').should('exist')



      page.get('bulk_action').select('Delete')
      page.get('action_submit_button').click()

      //page.get('modal_submit_button').should('be.visible')
      //page.get('modal_submit_button').click()
      cy.get('button').contains('Confirm and Delete').first().click()

      cy.hasNoErrors()

      page.get('alert').should('be.visible')
      page.get('alert_success').should('be.visible')
      page.get('sites').should('have.length', counter - 1)
    })

    it('can switch sites', () => {
      page.get('global_menu').click()
      page.get('dropdown').find('a[href*="cp/msm/switch_to/'+counter+'"]').click()

      cy.hasNoErrors()

      //page.get('global_menu').should('have.text', 'Rspec Site ' + counter)
      cy.get('h1').contains('Rspec Site '+ counter)
    })

    it('color code a site', () => {
      page.get('sites').last().find('a').click()
      cy.get('[name=site_label]').invoke('val').then((val) => {
        expect(val).to.eq('Rspec Site ' + counter)
      })
      cy.get('[name=site_label]').type('edited')
      cy.get('[name=site_name]').invoke('val').then((val) => {
        expect(val).to.eq('rspec_site_'+counter)
      })
      cy.get('[name=site_name]').type('edited')
      cy.get('[data-toggle-for="is_site_on"]').click()
      cy.get('[data-toggle-for="custom_site_color"]').click()
      cy.get('[name="site_color"]').should('be.visible');
      cy.get('[name="site_color"]').clear().type('aabbcc');
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      cy.visit('admin.php?/cp/msm');
      page.get('sites').last().find('td:nth-child(2)').contains('Rspec Site ' + counter + 'edited')
      page.get('sites').last().find('td:nth-child(3)').contains('{rspec_site_'+counter+'edited}')
      page.get('sites').last().find('td:nth-child(4)').contains('Offline')

      page.get('global_menu').click()
      page.get('dropdown').find('a[href*="cp/msm/switch_to/'+counter+'"]').click()
      cy.get('.ee-sidebar__title').should('have.css', 'backgroundColor').and('equal', 'rgb(170, 187, 204)');

      cy.visit('admin.php?/cp/msm');
      page.get('sites').last().find('a').click()
      cy.get('[name=site_label]').invoke('val').then((val) => {
        expect(val).to.eq('Rspec Site ' + counter + 'edited')
      })
      cy.get('[name=site_name]').invoke('val').then((val) => {
        expect(val).to.eq('rspec_site_'+counter+'edited')
      })
      cy.get('[data-toggle-for="is_site_on"]').should('not.have.class', 'on')
      cy.get('[data-toggle-for="custom_site_color"]').should('have.class', 'on')
      cy.get('[name="site_color"]').should('be.visible');
      cy.get('[data-toggle-for="custom_site_color"]').click()
      cy.get('body').type('{ctrl}', {release: false}).type('s')

      cy.get('.ee-sidebar__title').should('have.css', 'backgroundColor').and('equal', 'rgb(45, 46, 64)');
    })

  })
})
