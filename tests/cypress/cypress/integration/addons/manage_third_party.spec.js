/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
const page = new AddonManager;
const { _, $ } = Cypress

context('Add-On Manager Third-Party Table', function() {

    before(function() {
        cy.task('db:seed')
        cy.task('filesystem:copy', { from: 'support/add-on-manager/test_*', to: '../../system/user/addons/' })
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/addons/test_*')
    })

    beforeEach(function() {
        cy.authVisit(page.url);

        page.get('title').contains('Add-On Manager')
    })


    it('shows the Add-On Manager', function() {
        page.get('third_party_section').should('exist')

        // Third Party Heading
        page.get('third_party_heading').first().should('contain', 'Third Party Add-Ons')

        // Third Party Status Filter
        page.get('third_party_status_filter').should('exist')
        page.get('third_party_developer_filter').should('exist')

        // Third Party Addons
        page.get('third_party_addons').should('exist')
    })

    it('shows the third party add-ons', function() {
        page.get('third_party_addon_name_header').should('have.class', 'highlight')
        page.get('third_party_pagination').should('not.exist')
        page.get('third_party_addons').its('length').should('eq', 6)
    })


    it('can reverse sort by Add-On name', function() {
        let addons = [...page.$('third_party_addon_names').map(function(index, el) {
            return $(el).text();
        })];

        page.get('third_party_addon_name_header').find('a.sort').click().then(function() {
            cy.hasNoErrors()
            page.get('third_party_addon_name_header').should('have.class', 'highlight')
            let addonsReversed = [...page.$('third_party_addon_names').map(function(index, el) {
                return $(el).text();
            })];
            expect(addonsReversed).to.deep.equal(addons.reverse())
        })
    })

    it('can filter by status', function() {
        // By installed
        page.get('third_party_status_filter').first().click()
        page.get('third_party_status_filter_menu').contains("installed").click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(installed)')
        page.get('third_party_pagination').should('not.exist')
        page.get('third_party_no_results').should('exist')

        // By uninstalled
        page.get('third_party_status_filter').first().click()
        page.get('third_party_status_filter_menu').contains("uninstalled").click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(uninstalled)')
        page.get('third_party_section').find('tr.not-installed').should('exist')
        page.get('third_party_section').find('tr.not-installed').its('length').should('eq', 6)
        page.get('third_party_pagination').should('not.exist')
        page.get('third_party_addons').its('length').should('eq', 6)

        // By 'needs updates'
        page.get('third_party_status_filter').first().click()
        page.get('third_party_status_filter_menu').contains("needs updates").click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(needs updates)')
        page.get('third_party_section').find('tr.not-installed').should('not.exist')
        page.get('third_party_pagination').should('not.exist')
        page.get('third_party_no_results').should('exist')
    })

    it('can filter by developer', function() {
        // First by Test LLC
        page.get('third_party_developer_filter').contains('developer').click()
        page.get('third_party_developer_filter_menu').contains("Test LLC").click()
        cy.hasNoErrors()

        page.get('third_party_developer_filter').contains('(Test LLC)')
        page.get('third_party_addons').its('length').should('eq', 2)

        // Now by Example Inc.
        page.get('third_party_developer_filter').contains('developer').click()
        page.get('third_party_developer_filter_menu').contains("Example Inc.").click()
        cy.hasNoErrors()

        page.get('third_party_developer_filter').contains('(Example Inc.)')
        page.get('third_party_addons').its('length').should('eq', 4)
    })

    it('retains filters on sort', function() {
        // Filter on status
        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains('uninstalled').click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(uninstalled)')
        page.get('third_party_section').find('tr.not-installed').should('exist')
        page.get('third_party_addons').its('length').should('eq', 6)

        // Sort by Version
        page.get('third_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(uninstalled)')
        page.get('third_party_version_header').should('have.class', 'highlight')
    })

    it('retains sort on filtering', function() {
        // Reverse sort by Version
        let versions = [...page.$('third_party_versions').map(function(index, el) {
            return $(el).text();
        })];

        page.get('third_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('third_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('third_party_version_header').should('have.class', 'highlight')

        // Filter by Status
        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains("uninstalled").click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(uninstalled)')
        page.get('third_party_version_header').should('have.class', 'highlight')
    })

    it('can combine filters', function() {
        // First by installed
        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains('uninstalled').click()
        cy.hasNoErrors()

        page.get('third_party_section').find('tr.not-installed').should('exist')

        // Now by Developer
        page.get('third_party_developer_filter').contains('developer').click()
        page.get('third_party_developer_filter_menu').contains("Test LLC").click()
        cy.hasNoErrors()

        page.get('third_party_status_filter').contains('(uninstalled)')
        page.get('third_party_developer_filter').contains('(Test LLC)')
    })

    it('can bulk-install add-ons', function() {
        let addons = [...page.$('third_party_addon_names').map(function(index, el) {
            return $(el).text();
        })];

        page.get('third_party_checkbox_header').find('input[type = "checkbox"]').check()
        page.get('third_party_bulk_action').select("Install")
        page.get('third_party_action_submit_button').click()
        cy.hasNoErrors()

        // The filter should not changed
        page.hasAlert()
        page.get('alert').contains("Add-Ons Installed")

        addons.slice(0, 4).forEach(function(addon) {
            page.get('alert').contains(addon);
        });

        page.get('alert').contains("and " + (addons.length - 4) + " others...")
    })
    it('can uninstall add-ons', function() {
        // First by installed
        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains("installed").click()
        cy.hasNoErrors()

        let addons = [...page.$('third_party_addon_names').map(function(index, el) {
            return $(el).text();
        })];
        page.get('third_party_checkbox_header').find('input[type="checkbox"]').check()
        page.get('third_party_bulk_action').select("Uninstall")
        page.get('third_party_action_submit_button').click()
        page.get('modal_submit_button').click() // Submits a form
        cy.hasNoErrors()

        // The filter should not change
        page.get('third_party_status_filter').contains("(installed)")
        page.hasAlert()
        page.get('alert').contains("Add-Ons Uninstalled")
        addons.slice(0, 4).forEach(function(addon) {
            page.get('alert').contains(addon);
        });

        page.get('alert').contains("and " + (addons.length - 4) + " others...")
    })

    it('can install a single add-on', function() {
        let addon_name = page.$('third_party_addon_names').first().text()

        // Header at 0, first "real" row is 1
        page.get('third_party_addons').eq(0).find('ul.toolbar li.txt-only a.add').click()
        cy.hasNoErrors()

        // The filter should not change
        page.hasAlert()
        page.get('alert').contains("Add-Ons Installed")
        page.get('alert').contains(addon_name)

        page.get('third_party_status_filter').contains('status').click()
        page.get('third_party_status_filter_menu').contains('uninstalled').click()
        cy.hasNoErrors()

        page.get('third_party_addons').contains(addon_name).should('not.exist')
    })

    describe("With Add-Ons installed", function() {
        it('can sort by Version', function() {
            page.installThirdPartyAddons()
            let versions = [...page.$('third_party_versions').map(function(index, el) {
                return $(el).text();
            })];

            page.get('third_party_version_header').find('a.sort').click().then(function() {
                cy.hasNoErrors()
                page.get('third_party_version_header').should('have.class', 'highlight')

                let versionsSorted = [...page.$('third_party_versions').map(function(index, el) {
                    return $(el).text();
                })];

                expect(versionsSorted).to.not.deep.equal(versions)
                expect(versionsSorted[0]).equal('1.1')
                expect(versionsSorted[versionsSorted.length - 1]).equal('1.6')
            })
        })

        it('can reverse sort by Version', function() {
            page.installThirdPartyAddons()
            let versions = [...page.$('third_party_versions').map(function(index, el) {
                return $(el).text();
            })];

            page.get('third_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('third_party_version_header').find('a.sort').click().then(function() {
                cy.hasNoErrors()

                page.get('third_party_version_header').should('have.class', 'highlight')

                let versionsSorted = [...page.$('third_party_versions').map(function(index, el) {
                    return $(el).text();
                })];

                expect(versionsSorted).to.not.deep.equal(versions)
                expect(versionsSorted[0]).equal('1.6')
                expect(versionsSorted[versionsSorted.length - 1]).equal('1.1')
            })

            page.get('third_party_pagination').should('not.exist')

        })
        it('displays an itemized modal when attempting to uninstall 5 or less add-on', function() {
            page.installThirdPartyAddons()
                // First by installed
            page.get('third_party_status_filter').contains('status').click()
            page.get('third_party_status_filter_menu').contains("installed").click()
            cy.hasNoErrors()

            let addon_name = page.$('third_party_addon_names').first().text()

            // Header at 0, first "real" row is 1
            page.get('third_party_addons').eq(0).find('input[type="checkbox"]').check()
            page.get('third_party_bulk_action').select("Uninstall")
            page.get('third_party_action_submit_button').click()

            page.get('modal_title').contains("Confirm Uninstall")
            page.get('modal').contains("You are attempting to uninstall the following items, please confirm this action.")
            page.get('modal').contains(addon_name)
            page.get('modal').find('.checklist li').its('length').should('eq', 1)
        })
        it('displays a bulk confirmation modal when attempting to uninstall more than 5 add-ons', function() {
                page.installThirdPartyAddons()
                    // First by installed
                page.get('third_party_status_filter').contains('status').click()
                page.get('third_party_status_filter_menu').contains("installed").click()
                cy.hasNoErrors()

                page.get('third_party_checkbox_header').find('input[type="checkbox"]').check()
                page.get('third_party_bulk_action').select("Uninstall")
                page.get('third_party_action_submit_button').click()

                page.get('modal_title').contains("Confirm Uninstall")
                page.get('modal').contains("You are attempting to uninstall the following items, please confirm this action.")
                page.get('modal').contains('Add-On: 6 Add-Ons')
            })
            // The settings buttons "work" (200 response)
            // it('can navigate to a settings page', function() {
            // page.installThirdPartyAddons()
            //   page.get('phrase_search.clear().type('Rich Text Editor'
            //   page.get('search_submit_button.click
            //   cy.hasNoErrors()
            //
            //   page.get('find('ul.toolbar li.settings a').click
            //   cy.hasNoErrors()
            // })

        // The guide buttons "work" (200 response)
        it('can navigate to a manual page', function() {
            page.installThirdPartyAddons()
            page.get('third_party_addons').eq(0).find('ul.toolbar li.manual a').click()
            cy.hasNoErrors()
        })

        // @TODO - Test updating a single add-on
        // @TODO - Test bulk updating add-ons
    })
})