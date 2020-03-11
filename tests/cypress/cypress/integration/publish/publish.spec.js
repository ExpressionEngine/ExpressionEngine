/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/AddonManager';
const page = new AddonManager;

context('Publish Page - Create', () => {


    beforeEach(function() {
				cy.authVisit(page.url);
				cy.hasNoErrors();

        page.get('title').contains('Publish Page - Create')
		})
		
		it('shows a 404 if there is no channel id', function() {

			cy.contains("404")
	})


    it("filters independently", function() {
        page.get('first_party_status_filter').contains("status")
        page.get('third_party_status_filter').contains("status")

        page.get('first_party_status_filter').contains('status').click()
        page.get('first_party_status_filter_menu').contains("installed").click()
        cy.hasNoErrors()

        page.get('first_party_status_filter').contains("(installed)")
        page.get('third_party_status_filter').contains("status")

        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains("uninstalled").click()
        cy.hasNoErrors()

        page.get('first_party_status_filter').contains("(installed)")
        page.get('third_party_status_filter').contains("(uninstalled)")
    })

    it("sorts independently", function() {
        page.get('first_party_addon_name_header').should('have.class', 'highlight')
        page.get('third_party_addon_name_header').should('have.class', 'highlight')

        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')
        page.get('third_party_addon_name_header').should('have.class', 'highlight')

        page.get('third_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')
        page.get('third_party_version_header').should('have.class', 'highlight')
    })

    describe("keeps sort when paging the other table", function() {
        it("can sort First Party & page Third Party", function() {
            page.get('first_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('first_party_version_header').should('have.class', 'highlight')
        })

        it("can sort Third Party & page First Party", function() {
            page.get('third_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('third_party_version_header').should('have.class', 'highlight')
        })
    })

    describe("keeps sort when filtering the other table", function() {
        it("can sort First Party & page Third Party", function() {
            page.get('first_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('first_party_version_header').should('have.class', 'highlight')

            page.get('third_party_status_filter').contains('status').click()
            page.get('third_party_status_filter_menu').contains("uninstalled").click()
            cy.hasNoErrors()

            page.get('first_party_version_header').should('have.class', 'highlight')
            page.get('third_party_status_filter').contains("(uninstalled)")
        })

        it("can sort Third Party & page First Party", function() {
            page.get('third_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('third_party_version_header').should('have.class', 'highlight')

            page.get('first_party_status_filter').contains('status').click()
            page.get('first_party_status_filter_menu').contains("installed").click()
            cy.hasNoErrors()

            page.get('first_party_status_filter').contains("(installed)")
            page.get('third_party_version_header').should('have.class', 'highlight')
        })
    })

    describe("keeps the filter when paging the other table", function() {
        it("can filter First Party & page Third Party", function() {
            page.get('first_party_status_filter').contains('status').click()
            page.get('first_party_status_filter_menu').contains("installed").click()
            cy.hasNoErrors()

            page.get('first_party_status_filter').contains("(installed)")
        })

        it("can filter Third Party & page First Party", function() {
            page.get('third_party_status_filter').contains('status').click()
            page.get('third_party_status_filter_menu').contains("uninstalled").click()
            cy.hasNoErrors()

            page.get('third_party_status_filter').contains("(uninstalled)")
        })
    })

    describe("keeps the filter when sorting the other table", function() {
        it("can filter First Party & page Third Party", function() {
            page.get('first_party_status_filter').contains('status').click()
            page.get('first_party_status_filter_menu').contains("installed").click()
            cy.hasNoErrors()

            page.get('first_party_status_filter').contains("(installed)")

            page.get('third_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('first_party_status_filter').contains("(installed)")
            page.get('third_party_version_header').should('have.class', 'highlight')
        })

        it("can filter Third Party & page First Party", function() {
            page.get('third_party_status_filter').contains('status').click()
            page.get('third_party_status_filter_menu').contains("uninstalled").click()
            cy.hasNoErrors()

            page.get('third_party_status_filter').contains("(uninstalled)")

            page.get('first_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('first_party_version_header').should('have.class', 'highlight')
            page.get('third_party_status_filter').contains("(uninstalled)")
        })
    })

})