/// <reference types="Cypress" />

import RteSettings from '../../elements/pages/addons/RteSettings';
const page = new RteSettings;
const { _, $ } = Cypress

context('RTE Settings', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('db:load', 'rte-settings/tool_sets.sql')
    })

    beforeEach(function() {
        cy.authVisit(page.url);

        page.get('title').contains('Rich Text Editor')
    })

    describe('Settings', function() {

        it('shows the RTE Settings page', function() {
            page.get('rte_enabled').should('have.value', 'y')
            page.get('default_tool_set').eq(1)
        })

        it('can navigate back to the add-on manager via the breadcrumb', function() {
            page.get('breadcrumb').find('a').click()
            cy.hasNoErrors()

            cy.title().should('include', 'Add-On Manager')
        })

        it('can disable & enable the rich text editor', function() {
            page.get('rte_enabled_toggle').click()
            page.get('save_settings_button').click()
            cy.hasNoErrors()

            page.get('rte_enabled').should('have.value', 'n')

            page.get('rte_enabled_toggle').click()
            page.get('save_settings_button').click()
            cy.hasNoErrors()

            page.get('rte_enabled').should('have.value', 'y')
        })

        it('only accepts "y" or "n" for enabled setting', function() {
            page.get('rte_enabled').invoke('attr', 'value', '1')
            page.get('save_settings_button').click()
            cy.hasNoErrors()

            page.hasAlert('error')

            // page.get('rte_enabled').invoke('attr', 'value', 'yes')
            // page.get('save_settings_button').click()
            // cy.hasNoErrors()

            // page.hasAlert('error')
        })

        it('cannot set a default tool set to an nonexistent tool set', function() {
            page.get('default_tool_set').eq(0).invoke('attr', 'value', '99999')
            page.get('default_tool_set').eq(0).check()
            page.get('save_settings_button').click()
            cy.hasNoErrors()

            page.hasAlert('error')
        })

        it('can disable & enable a single tool set', function() {
            page.get('tool_sets').eq(1).contains('Enabled')

            page.get('tool_sets').eq(1).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Disable")
            page.get('action_submit_button').click()
            cy.hasNoErrors()

            page.get('tool_sets').eq(1).contains('Disabled')
            page.get('tool_sets').eq(1).contains('Enabled').should('not.exist')

            page.get('tool_sets').eq(1).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Enable")
            page.get('action_submit_button').click()
            cy.hasNoErrors()

            page.get('tool_sets').eq(1).contains('Enabled')
            page.get('tool_sets').eq(1).contains('Disabled').should('not.exist')
        })

        it('can disable & enable multiple tool set', function() {
            cy.document().contains('Enabled')
            cy.document().contains('Disabled').should('not.exist')

            page.get('checkbox_header').find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Disable")
            page.get('action_submit_button').click()
            cy.hasNoErrors()

            cy.document().contains('Disabled')

            page.get('checkbox_header').find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Enable")
            page.get('action_submit_button').click()
            cy.hasNoErrors()

            cy.document().contains('Enabled')
            cy.document().contains('Disabled').should('not.exist')
        })

        it('displays an itemized modal when trying to remove 5 or less tool sets', function() {
            let tool_set_name = page.$('tool_set_names').eq(0).text()

            // Header at 0, first "real" row is 1
            page.get('tool_sets').eq(1).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Remove")
            page.get('action_submit_button').click()

            page.get('modal_title').contains("Confirm Removal")
            page.get('modal').contains("You are attempting to remove the following items, please confirm this action.")
            page.get('modal').contains(tool_set_name)
            page.get('modal').find('.checklist li').its('length').should('eq', 1)
        })

        it('displays a bulk confirmation modal when trying to remove more than 5 tool sets', function() {
            page.get('checkbox_header').find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Remove")
            page.get('action_submit_button').click()

            page.get('modal_title').contains("Confirm Removal")
            page.get('modal').contains("You are attempting to remove the following items, please confirm this action.")
            page.get('modal').contains("Tool Set: 6 Tool Sets")
        })

        it('cannot remove the default tool set', function() {
            let tool_set_name = page.$('tool_set_names').eq(1).text()

            // This populates the modal with a hidden input so we can modify it later
            page.get('tool_sets').eq(1).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Remove")
            page.get('action_submit_button').click()

            let tool_set_id = page.$('tool_sets').eq(2).find('input[type="checkbox"]').val()
            cy.get('input[name="selection[]"]').invoke('attr', 'value', tool_set_id)

            page.get('modal_submit_button').click() // Submits a form
            cy.hasNoErrors()

            page.hasAlert('error')
            page.get('alert').contains("The default RTE tool set cannot be removed")
            page.get('tool_set_names').eq(1).contains(tool_set_name)
        })

        it('can reverse sort tool sets by name', function() {
            let toolsets = [...page.$('tool_set_names').map(function(index, el) { return $(el).text(); })];

            page.get('tool_set_name_header').find('a.sort').click().then(function() {
                cy.hasNoErrors()

                page.get('tool_set_name_header').should('have.class', 'highlight')
                let toolsetsReversed = [...page.$('tool_set_names').map(function(index, el) { return $(el).text(); })];

                expect(toolsetsReversed).to.deep.equal(toolsets.reverse())
            })
        })

        it('can sort tool sets by status', function() {
            let beforeSorting = ['Enabled', 'Enabled', 'Enabled', 'Disabled', 'Enabled', 'Enabled', 'Enabled']
            let aToZ = ['Disabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled']
            let zToA = ['Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Enabled', 'Disabled']

            // page.get('tool_sets').eq(2).find('input[type="checkbox"]').check()
            page.get('tool_sets').eq(4).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Disable")
            page.get('action_submit_button').click().then(function() {
                cy.hasNoErrors()

                // Confirm the right items disabled
                let statuses = [...page.$('statuses').map(function(index, el) { return $(el).text(); })];
                expect(statuses).to.deep.equal(beforeSorting)

                // Sort a-z
                page.get('status_header').find('a.sort').click().then(function() {
                    cy.hasNoErrors()

                    statuses = [...page.$('statuses').map(function(index, el) { return $(el).text(); })];
                    expect(statuses).to.deep.equal(aToZ)

                    // Sort z-a
                    page.get('status_header').find('a.sort').click().then(function() {
                        cy.hasNoErrors()

                        statuses = [...page.$('statuses').map(function(index, el) { return $(el).text(); })];
                        expect(statuses).to.deep.equal(zToA)
                    })
                })
            })
        })

        it('can change the default tool set', function() {
            page.get('default_tool_set').eq(3).check()
            page.get('save_settings_button').click()
            cy.hasNoErrors()

            page.get('default_tool_set').eq(3).should('be.checked')
        })

        it('can edit a tool set', function() {
            page.get('tool_sets').eq(1).find('li.edit a').click()
            cy.hasNoErrors()

            page.get('tool_set_name').clear().type('Cypress Edited')
            page.get('tool_set_save_button').click()

            cy.hasNoErrors()
            page.get('tool_set_name').invoke('val').should('eq', "Cypress Edited")

            page.hasAlert('success')
            page.get('alert').contains("Tool set updated")
        })

        it('can remove a tool set', function() {
            page.get('tool_sets').eq(1).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("Remove")
            page.get('action_submit_button').click()
            page.get('modal_submit_button').click() // Submits a form
            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains("Tool sets removed")
            page.get('alert').contains("The following tool sets were removed")
            page.get('alert').contains("Cypress Edited")
        })

        it('can bulk remove tool sets', function() {
            page.get('checkbox_header').find('input[type="checkbox"]').check()

            // Uncheck the Default tool set
            page.get('bulk_action').select("Remove")
            page.get('action_submit_button').click()
            page.get('modal_submit_button').click() // Submits a form
            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains("Tool sets removed")
            page.get('alert').contains("The following tool sets were removed")
            page.get('alert').contains("Even")
            page.get('alert').contains("Everything")
            page.get('alert').contains("Default")
            page.get('alert').contains("Odd")
            page.get('alert').contains("Simple")
        })
    })

    describe('Toolsets', function() {
        beforeEach(function() {
            page.get('create_new_button').click()
        })

        it('can navigate back to settings from tool set', function() {
            page.get('breadcrumb').find('li:nth-child(2) a').click()
            cy.hasNoErrors()

            page.confirmSettings()
        })

        it('can create a new tool set', function() {
            page.get('tool_set_name').type('Empty')
            page.get('tool_set_save_and_close_button').click()

            cy.hasNoErrors()
            page.confirmSettings()
            page.hasAlert('success')
            page.get('alert').contains("Tool set created")
            page.get('alert').contains("Empty has been successfully created.")

            cy.get('tr.selected').should('exist')
            cy.get('tr.selected').contains("Empty")
        })

        it('ensures tool set names are unique', function() {
            page.get('tool_set_name').type('Empty')
            page.get('tool_set_save_and_close_button').click()

            cy.hasNoErrors()
            page.get('tool_set_name').invoke('val').should('eq', "Empty")

            page.hasAlert('error')
            page.get('alert').contains("Tool set error")
            page.get('alert').contains("We were unable to save the tool set, please review and fix errors below.")

            cy.contains('The tool set name must be unique')
        })

        it('requires a tool set name', function() {
            page.get('tool_set_save_and_close_button').click()

            cy.hasNoErrors()
                // page.confirmToolset()
            page.get('tool_set_name').invoke('val').should('eq', '')

            page.hasAlert('error')
            page.get('alert').contains("Tool set error")
            page.get('alert').contains("We were unable to save the tool set, please review and fix errors below.")

            cy.contains('This field is required')
        })

        it('disallows XSS strings as a tool set name', function() {
            page.get('tool_set_name').type('<script>Haha')
            page.get('tool_set_save_and_close_button').click()

            cy.hasNoErrors()
                // page.confirmToolset()
            page.get('tool_set_name').invoke('val').should('eq', "<script>Haha")

            page.hasAlert('error')
            page.get('alert').contains("Tool set error")
            page.get('alert').contains("We were unable to save the tool set, please review and fix errors below.")

            cy.contains('The tool set name must not include special characters')
        })

        it('persists tool checkboxes on validation errors', function() {
            page.get('choose_tools').eq(0).click()
            page.get('choose_tools').eq(1).click()
            page.get('choose_tools').eq(2).click()

            page.get('tool_set_save_and_close_button').click()

            cy.hasNoErrors()
                // page.confirmToolset()
            page.get('choose_tools').eq(0).should('be.checked')
            page.get('choose_tools').eq(1).should('be.checked')
            page.get('choose_tools').eq(2).should('be.checked')
        })
    })

})
