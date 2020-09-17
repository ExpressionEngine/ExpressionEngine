/// <reference types="Cypress" />

import AddonManager from '../../elements/pages/addons/AddonManager';
const page = new AddonManager;
const { _, $ } = Cypress

context('Add-On Manager First-Party Table Only', function() {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);

        page.get('title').contains('Add-On Manager')
    })


    it('shows the Add-On Manager', function() {
        page.get('first_party_section').should('exist')
        page.get('third_party_section').should('not.exist')

        // First Party Heading
        page.get('first_party_heading').first().should('contain', 'Add-Ons')

        // First Party Status Filter
        page.get('first_party_status_filter').should('exist')

        // First Party Addons
        page.get('first_party_addons').should('exist')
        page.get('first_party_addon_name_header').should('have.class', 'highlight')
    })

    it('can reverse sort by Add-On name', function() {
        let addons = [...page.$('first_party_addon_names').map(function(index, el) {
            return $(el).text();
        })];

        page.get('first_party_addon_name_header').find('a.sort').click()
        cy.hasNoErrors()
        page.get('first_party_addon_name_header').should('have.class', 'highlight')

        page.get('first_party_addon_names').then(function($td) {
            let addonsReversed = _.map($td, function(el) {
                return $(el).text();
            })

            expect(addonsReversed).to.deep.equal(addons.reverse())
        })

        page.get('first_party_pagination').should('not.exist')
    })

    it('can sort by Version', function() {
        let versions = [...page.$('first_party_versions').map(function(index, el) {
            return $(el).text();
        })];

        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')

        page.get('first_party_versions').then(function($td) {
            let versionsSorted = _.map($td, function(el) {
                return $(el).text();
            })

            expect(versionsSorted).to.not.deep.equal(versions)
            expect(versionsSorted[0]).to.equal('--')
        })

        page.get('first_party_pagination').should('not.exist')
    })

    it('can reverse sort by Version', function() {
        let versions = [...page.$('first_party_versions').map(function(index, el) {
            return $(el).text();
        })];

        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')

        page.get('first_party_versions').then(function($td) {
            let versionsSorted = _.map($td, function(el) {
                return $(el).text();
            })

            expect(versionsSorted).to.not.deep.equal(versions)
            expect(versionsSorted[versionsSorted.length - 1]).to.equal('--')
        })

        page.get('first_party_pagination').should('not.exist')
    })

    it('can filter by status', function() {
        // By installed
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('installed').click()

        cy.hasNoErrors()

        page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(installed)')

        cy.get('tr.not-installed').should('not.exist')
        page.get('first_party_pagination').should('not.exist')

        page.get('first_party_addons').its('length').should('eq', 6)

        // By uninstalled
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('uninstalled').click()

        cy.hasNoErrors()

        page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(uninstalled)')
        cy.get('tr.not-installed').should('exist')
        cy.get('tr.not-installed').its('length').should('eq', 14)
        page.get('first_party_addons').its('length').should('eq', 14)

        // By 'needs updates'
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('needs updates').click()

        cy.hasNoErrors()

        page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(needs updates)')
        cy.get('tr.not-installed').should('not.exist')
        page.get('first_party_pagination').should('not.exist')

        // RTE has the correct version number now
        page.get('first_party_addons').its('length').should('eq', 3)
    })

    it('retains filters on sort', function() {
        // Filter on status
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('installed').click()

        cy.hasNoErrors()

        page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(installed)')
        cy.get('tr.not-installed').should('not.exist')

        page.get('first_party_versions').then(function($td) {
            let versions = _.map($td, function(el) {
                return $(el).text();
            })

            // Sort by Version
            page.get('first_party_version_header').find('a.sort').click()
            cy.hasNoErrors()

            page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(installed)')
        })
    })

    it('retains sort on filtering', function() {
        // Reverse sort by Version
        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').find('a.sort').click()
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')

        let versionsSorted = [...page.$('first_party_versions').map(function(index, el) {
            return $(el).text();
        })];

        // Filter by Status
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('installed').click()
        page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(installed)').then(function() {
            let versionsFiltered = [...page.$('first_party_versions').map(function(index, el) {
                return $(el).text();
            })];
            // console.log({ versionsFiltered, versionsSorted });
            expect(versionsSorted).to.not.deep.equal(versionsFiltered)
            expect(versionsFiltered[versionsFiltered.length - 1]).to.equal('1.0.0')
        })
        cy.hasNoErrors()

        page.get('first_party_version_header').should('have.class', 'highlight')
    })

    it('can install a single add-on', function() {
        // First by uninstalled
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains('uninstalled').click()
        cy.hasNoErrors()

        page.get('first_party_addon_names').first().then(function(el) {
            const addon_name = $(el).text();
            // Header at 0, first "real" row is 1
            page.get('first_party_addons').first().find('ul.toolbar li.txt-only a.add').click()
            cy.hasNoErrors()

            // The filter should not change
            page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(uninstalled)')
            page.hasAlert()
            //page.get('alert').first().invoke('text').should('include', 'Add-Ons Installed')
          
            cy.get('body').contains('Add-Ons Installed')
            page.get('first_party_addons').contains(addon_name).should('not.exist')
        })
    })

    it('can bulk-install add-ons', function() {
        // First by installed
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains("uninstalled").click().then(function() {
            cy.hasNoErrors()

            let addons = [...page.$('first_party_addon_names').map(function(index, el) {
                return $(el).text();
            })];

            // Header at 0, first "real" row is 1
            page.get('first_party_checkbox_header').find('input[type="checkbox"]').check()
            page.get('first_party_bulk_action').select("Install")
            page.get('first_party_action_submit_button').click()
            cy.hasNoErrors()

            // The filter should not change
            page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(uninstalled)')
            page.hasAlert()
            // page.get('alert').first().invoke('text').should('include', 'Add-Ons Installed')
            cy.get('body').contains('Add-Ons Installed')
            page.get('first_party_no_results').should('exist')
            page.get('first_party_pagination').should('not.exist')
            page.get('first_party_bulk_action').should('not.exist')
        })
    })

    // The settings buttons "work"(200 response)
    it('can navigate to a settings page', function() {
        cy.get('ul.toolbar li.settings a[title="Settings"]').first().click()
        cy.hasNoErrors()
    })

    // The guide buttons "work"(200 response)
    it('can navigate to a manual page', function() {
        cy.get('ul.toolbar li.manual a[title="Manual"]').first().click()
        cy.hasNoErrors()
    })

    it('displays an itemized modal when attempting to uninstall 5 or less add-on', function() {
        // First by installed
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains("installed").click().then(function() {
            cy.hasNoErrors()
            let addon_name = page.$('first_party_addon_names').first().text()

            // Header at 0, first "real" row is 1
            page.get('first_party_addons').eq(0).find('input[type="checkbox"]').check()
            page.get('first_party_bulk_action').select("Uninstall")
            page.get('first_party_action_submit_button').click()

            page.get('modal_title').contains("Confirm Uninstall")
            page.get('modal').contains("You are attempting to uninstall the following items, please confirm this action.")
            page.get('modal').find('.checklist li').its('length').should('eq', 1)
            page.get('modal').contains(addon_name)
        })
    })


    it('displays a bulk confirmation modal when attempting to uninstall more than 5 add-ons', function() {
        //   // First by installed
        //   page.get('first_party_status_filter').click
        //   page.get('wait_until_first_party_status_filter_menu_visible')
        //   page.get('first_party_status_filter_menu').click_link "installed"
        //   cy.hasNoErrors()
        //
        //   page.get('first_party_checkbox_header').find('input[type="checkbox"]').check()
        //   page.get('wait_until_first_party_bulk_action_visible')
        //   page.get('first_party_bulk_action').select "Uninstall"
        //   page.get('first_party_action_submit_button').click
        //
        //   page.get('wait_until_modal_visible')
        //   page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Uninstall"
        //   page.get('modal').contains("You are attempting to uninstall the following items, please confirm this action."
        //   page.get('modal').contains('Add-On: 17 Add-Ons'
    })

    it('can uninstall add-ons', function() {
        // First by installed
        page.get('first_party_status_filter').first().click()
        page.get('first_party_status_filter_menu').contains("installed").click().then(function() {
            cy.hasNoErrors()

            let addons = [...page.$('first_party_addon_names').map(function(index, el) {
                return $(el).text();
            })];

            page.get('first_party_checkbox_header').find('input[type="checkbox"]').check()
            page.get('first_party_bulk_action').select("Uninstall")
            page.get('first_party_action_submit_button').click()
            page.get('modal_submit_button').contains('Confirm, and Uninstall').click() // Submits a form
            cy.hasNoErrors()

            // The filter should not change
            page.get('first_party_status_filter').find('> a').invoke('text').should('contain', '(installed)')
            page.hasAlert()

            page.get('alert').contains("Add-Ons Uninstalled")

            addons.slice(0, 4).forEach(function(addon) {
                page.get('alert').contains(addon);
            });

            page.get('alert').contains("and " + (addons.length - 4) + " others...")
        })
    })

    // @TODO - Test updating a single add - on
    // @TODO - Test bulk updating add - ons
})