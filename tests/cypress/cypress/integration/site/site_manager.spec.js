/// <reference types="Cypress" />

import SiteManager from '../../elements/pages/site/SiteManager';
import SiteForm from '../../elements/pages/site/SiteForm';

const page = new SiteManager;

let counter = 1;

context('Site Manager', () => {

  before(function(){
    cy.task('db:seed')
  })

  beforeEach(function() {
    cy.eeConfig({item: 'multiple_sites_enabled', value: 'y'})
    cy.auth();
    page.load();
    cy.hasNoErrors()
  })

  /*it('displays', () => {
    page.all_there?.should('eq', true
  })*/

  context('with multiple sites', () => {
    beforeEach(function() {

      counter++;

      page.get('add_site_button').click()
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
      page.get('sites').eq(1).find('td:first-child').should('have.text', '2')
      page.get('sites').eq(1).find('td:nth-child(2)').should('have.text', 'Rspec Site ' + counter)
      page.get('sites').eq(1).find('td:nth-child(3)').should('have.text', '{rspec_site_'+counter+'}')
      page.get('sites').eq(1).find('td:nth-child(4)').invoke('text').then((text) => { expect(text.toUpperCase()).to.be.equal('ONLINE') })
    })

    it('can delete a site', () => {
      page.get('sites').eq(1).find('td:nth-child(6) input').click()

      page.get('bulk_action').should('exist')
      page.get('action_submit_button').should('exist')



      page.get('bulk_action').select('Remove')
      page.get('action_submit_button').click()

      page.get('modal_submit_button').should('be.visible')
      page.get('modal_submit_button').click()

      cy.hasNoErrors()

      page.get('alert').should('be.visible')
      page.get('alert_success').should('be.visible')
      page.get('sites').should('have.length', counter - 1)
    })

    it('can switch sites', () => {
      page.get('global_menu').find('.nav-sites a.nav-has-sub').click()
      page.get('global_menu').find('a[href*="cp/msm/switch_to/'+counter+'"]').click()

      cy.hasNoErrors()

      page.get('global_menu').find('.nav-sites a.nav-has-sub').should('have.text', 'Rspec Site ' + counter)
    })
  })
})
