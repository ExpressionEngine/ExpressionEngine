/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';

context('Design', () => {
	before(function () {
		cy.task('db:seed')
		cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
		cy.eeConfig({ item: 'multiple_sites_enabled', value: 'y' })

		//copy templates
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
			cy.authVisit('admin.php?/cp/design')
		})

		const siteManager = new SiteManager;
		siteManager.load();

		cy.get('.main-nav a').contains('Add Site').first().click()

		const form = new SiteForm
		form.add_site({
			name: 'Second Site',
			short_name: 'second_site'
		})

		siteManager.get('global_menu').click()
		siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/2"]').click()
		cy.authVisit('admin.php?/cp/design')

		cy.hasNoErrors()

		siteManager.get('global_menu').click()
		siteManager.get('dropdown').find('a[href*="cp/msm/switch_to/1"]').click()
	})

	after(function () {
	})

	beforeEach(function () {
		cy.visit('index.php/resources/index')
	})

	afterEach(function () {
	})

	describe('loading of resources from templates', function () {
		it('loads stylesheet resource template from the current site', function () {
			cy.get("#first").should('have.css', 'background-color', 'rgb(0, 255, 255)')
		})

		it('loads script resource template from the current site', function () {
			cy.get("#second").should('have.css', 'background-color', 'rgb(255, 0, 255)')
		})

		it('loads stylesheet resource template from a different MSM site', function () {
			cy.get("#third").should('have.css', 'background-color', 'rgb(255, 255, 0)')
		})

		it('loads script resource template from a different MSM site', function () {
			cy.get("#fourth").should('have.css', 'background-color', 'rgb(0, 0, 0)')
		})

		it('loads stylesheet using `/css/` on path', function () {
			cy.get("#fourth").should('have.css', 'color', 'rgb(255, 255, 255)')
		})
	})
})
