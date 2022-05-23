/// <reference types="Cypress" />

import SiteForm from '../../elements/pages/site/SiteForm';
import SiteManager from '../../elements/pages/site/SiteManager';

const files = [
	'../../system/user/templates/default_site/resources.group/style.css',
	'../../system/user/templates/default_site/resources.group/script.js',
	'../../system/user/templates/second_site/resources.group/style.css',
]
const replaceColors = {
	'cyan': 'red',
	'magenta': 'lime',
	'yellow': 'blue',
}

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
		cy.task('filesystem:delete', '../../system/user/templates/default_site/resources.group')
		cy.task('filesystem:delete', '../../system/user/templates/second_site/resources.group')
	})

	beforeEach(function () {
		cy.visit('index.php/resources/index')
	})

	afterEach(function () {
	})

	describe('loading of resources from templates', function () {
		it('loads stylesheet resource template from the current site', function () {
			cy.logFrontendPerformance()
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
			cy.logFrontendPerformance()
			cy.get("#fourth").should('have.css', 'color', 'rgb(255, 255, 255)')
		})
	})

	describe('loading of resources from updated templates', function () {
		before(function () {
			files.forEach((file) => {
				cy.readFile(file, (err, data) => {
					if (err) {
					return console.error(err);
					};
				}).then((data) => {
					Object.keys(replaceColors).forEach((x)=> {
					data = data.replace(x, replaceColors[x]);
					})
					cy.writeFile(file, data);
				})
			})
		})

		it('loads stylesheet resource template from the current site', function () {
			cy.logFrontendPerformance()
			cy.get("#first").should('have.css', 'background-color', 'rgb(255, 0, 0)')
		})

		it('loads script resource template from the current site', function () {
			cy.get("#second").should('have.css', 'background-color', 'rgb(0, 255, 0)')
		})

		it('loads stylesheet resource template from a different MSM site', function () {
			cy.get("#third").should('have.css', 'background-color', 'rgb(0, 0, 255)')
		})
	})

	describe('loading of resources on a cached HTML template', function () {
		before(function () {
			cy.authVisit('admin.php?/cp/design/manager/resources')

			cy.get('.app-listing__row a').contains('index').click()

			cy.get('.js-tab-button.tab-bar__tab').contains('Settings').click()
			cy.get('[data-toggle-for="cache"][data-state="off"]').click()
			cy.get('button').contains('Save').click()

			// visit the page to save a cached version
			cy.visit('index.php/resources/index')
		})

		beforeEach(function () {
			cy.visit('index.php/resources/index')
		})

		it('respects the saved styles', function () {
			cy.get("#first").should('have.css', 'background-color', 'rgb(255, 0, 0)')
			cy.get("#second").should('have.css', 'background-color', 'rgb(0, 255, 0)')
			cy.get("#third").should('have.css', 'background-color', 'rgb(0, 0, 255)')
		})

		it('serves new styles', function () {
			files.forEach((file) => {
				cy.readFile(file, (err, data) => {
					if (err) {
						return console.error(err);
					};
				}).then((data) => {
					Object.keys(replaceColors).forEach((x) => {
						data = data.replace(replaceColors[x], x);
					})
					cy.writeFile(file, data);
				})
			})

			cy.get("#first").should('have.css', 'background-color', 'rgb(0, 255, 255)')

			cy.get("#second").should('have.css', 'background-color', 'rgb(255, 0, 255)')

			cy.get("#third").should('have.css', 'background-color', 'rgb(255, 255, 0)')
		})
	})
})
