/// <reference types="Cypress" />

import RteSettings from '../../elements/pages/addons/RteSettings';
const page = new RteSettings;
const { _, $ } = Cypress

context('Rich Text Editor', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('db:load', '../../support/sql/rte-settings/tool_sets.sql')
    })

    describe('RTE Settings', function() {

        it('Shows the RTE Settings page', function() {
            cy.intercept('**/check').as('check')
            cy.intercept('**/license/handleAccessResponse').as('license')
            cy.authVisit(page.url);
            page.get('title').contains('Rich Text Editor')
            cy.wait('@check')
            cy.wait('@license')

            page.get('default_tool_set').first().should('be.checked')
            page.get('tool_set_names').first().parent().should('have.class', 'default')
        })

        it.skip('can navigate back to the add-on manager via the breadcrumb', function() {
            cy.authVisit(page.url);
            page.get('breadcrumb').contains('Add-ons').click()
            cy.hasNoErrors()

            cy.title().should('include', 'Add-Ons')
        })

        it('Cannot set a default tool set to an nonexistent tool set', function() {
            cy.authVisit(page.url);
            page.get('default_tool_set').eq(0).invoke('attr', 'value', '99999')
            page.get('default_tool_set').eq(0).check()
            page.get('save_settings_button').eq(0).click()
            cy.hasNoErrors()

            page.hasAlert('error')
        })


        it('Displays an itemized modal when trying to remove 5 or less tool sets', function() {
            cy.authVisit(page.url);
            page.get('tool_set_names').eq(1).invoke('text').then((tool_set_name) => {
                // Header at 0, first "real" row is 1
                page.get('tool_sets').eq(2).find('input[type="checkbox"]').check()
                page.get('bulk_action').select("Delete")
                page.get('action_submit_button').click()

                page.get('modal_title').contains("Confirm Removal")
                page.get('modal').contains("You are attempting to remove the following items, please confirm this action.")
                page.get('modal').contains(tool_set_name)
                page.get('modal').find('.checklist li').its('length').should('eq', 1)
            })
        })

        it('Displays a bulk confirmation modal when trying to remove more than 5 tool sets', function() {
            cy.authVisit(page.url);
            page.get('checkbox_header').find('input[type="checkbox"]:enabled').check()
            page.get('bulk_action').select("Delete")
            page.get('action_submit_button').click()

            page.get('modal_title').contains("Confirm Removal")
            page.get('modal').contains("You are attempting to remove the following items, please confirm this action.")
            page.get('modal').contains("Tool Set: 6 Tool Sets")
        })

        it('Cannot remove the default tool set', function() {
            cy.authVisit(page.url);
            page.get('tool_set_names').eq(0).invoke('text').then((tool_set_name) => {
                // This populates the modal with a hidden input so we can modify it later
                page.get('tool_sets').eq(2).find('input[type="checkbox"]').check()
                page.get('bulk_action').select("Delete")
                page.get('action_submit_button').click()

                cy.get('input[name="selection[]"]').then(elem => {
                    cy.get('[value="Confirm, and Remove"]').first().invoke('val').then((val) => {
                        elem.val(val)
                    });
                });

                //page.get('modal_submit_button').click() // Submits a form AJ
                cy.get('button').contains('Confirm, and Remove').first().click({force:true})
                cy.hasNoErrors()

                page.hasAlert('error')
                page.get('alert').contains("Your Rich Text Editor Settings could not be saved")
                page.get('tool_set_names').eq(0).contains(tool_set_name)
            })
        })

        it('Can reverse sort tool sets by name', function() {
            cy.authVisit(page.url);
            page.get('tool_set_names').should('exist').then(() => {
                let toolsets = [...page.$('tool_set_names').map(function(index, el) { return $(el).text(); })];

                page.get('tool_set_name_header').find('a.column-sort').click();
                cy.hasNoErrors()
    
                page.get('tool_set_name_header').should('have.class', 'column-sort-header--active').then(() => {
                    let toolsetsReversed = [...page.$('tool_set_names').map(function(index, el) { return $(el).text(); })];
    
                    expect(toolsetsReversed).to.deep.equal(toolsets.reverse())
                })
            })
        })

        it('Can change the default tool set', function() {
            cy.authVisit(page.url);
            page.get('default_tool_set').last().check()
            page.get('save_settings_button').eq(0).click()
            cy.hasNoErrors()

            page.get('default_tool_set').last().should('be.checked')

            page.get('default_tool_set').first().check()
            page.get('save_settings_button').eq(0).click()
            cy.hasNoErrors()

            page.get('default_tool_set').first().should('be.checked')
        })

        it('Can edit a tool set', function() {
            cy.authVisit(page.url);
            page.get('tool_sets').eq(1).find('a').first().click()

            page.get('tool_set_name').clear().type('Cypress Edited')
            page.get('tool_set_save_button').eq(1).click() // save and close
            cy.get('button').contains('Save & Close').first().click()

            cy.hasNoErrors()
            page.get('tool_sets').contains("Cypress Edited")

            page.hasAlert('success')
            page.get('alert').contains("Tool set updated")
        })

        it('Can remove a tool set', function() {
            cy.authVisit(page.url);
            page.get('tool_sets').eq(3).find('input[type="checkbox"]').check()
            page.get('bulk_action').select("remove")
            page.get('action_submit_button').click()

            page.get('modal_submit_button').click() 

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains("Tool sets removed")
            page.get('alert').contains("The following tool sets were removed")

        })

        it('Can bulk remove tool sets', function() {
            cy.authVisit(page.url);
            page.get('checkbox_header').find('input[type="checkbox"]').check()

            // Uncheck the Default tool set
            page.get('bulk_action').select("Delete")
            page.get('action_submit_button').click()


            //page.get('modal_submit_button').click() // Submits a form new cp does not use this
            cy.get('[type="submit"][value="Confirm, and Remove"]').eq(1).click() //try this instead.

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains("Tool sets removed")
            page.get('alert').contains("The following tool sets were removed")
            page.get('alert').contains("CKEditor Full")
            page.get('alert').contains("RedactorX Basic")
            page.get('alert').contains("RedactorX Full")
            page.get('alert').contains("Six")
            page.get('alert').contains("Seven")
        })
    })

    describe('RTE Toolsets', function() {
        beforeEach(function() {
            cy.authVisit(page.url);
            page.get('title').contains('Rich Text Editor')
            cy.get('a').contains('Create New').filter(':visible').first().click({force:true})
        })

        it.skip('can navigate back to settings from tool set', function() {
            page.get('breadcrumb').find('a:contains("Rich Text Editor")').click()
            cy.hasNoErrors()

            page.confirmSettings()
        })

        it('Can create a new tool set', function() {
            page.get('tool_set_name').type('Empty')
            page.get('tool_set_save_button').eq(1).click() // save and close
            cy.get('button').contains('Save & Close').first().click()

            cy.hasNoErrors()
            page.confirmSettings()
            page.hasAlert('success')
            page.get('alert').contains("Tool set created")
            page.get('alert').contains("Empty has been successfully created.")

            page.get('tool_sets').contains("Empty")
        })

        it('Ensures tool set names are unique', function() {
            page.get('tool_set_name').type('Empty')
            page.get('tool_set_save_button').eq(0).click()

            cy.hasNoErrors()
            page.get('tool_set_name').invoke('val').should('eq', "Empty")

            page.hasAlert('error')
            page.get('alert').contains("Tool set error")
            page.get('alert').contains("We were unable to save the tool set, please review and fix errors below.")

            cy.contains('This field must be unique.')
        })

        it('Requires a tool set name', function() {
            page.get('tool_set_save_button').eq(1).click() // save and close
            cy.get('button').contains('Save & Close').first().click()

            cy.hasNoErrors()

            cy.hasNoErrors()

            page.hasAlert('success')
            page.get('alert').contains("Tool set created")
            page.get('alert').contains("Untitled has been successfully created.")

            page.get('tool_sets').contains("Untitled")
        })

        it('Disallows XSS strings as a tool set name', function() {
            page.get('tool_set_name').type('<script>Haha')
            page.get('tool_set_save_button').eq(0).click()

            cy.hasNoErrors()
                // page.confirmToolset()
            page.get('tool_set_name').invoke('val').should('eq', "<script>Haha")

            page.hasAlert('error')
            page.get('alert').contains("Tool set error")
            page.get('alert').contains("We were unable to save the tool set, please review and fix errors below.")

            cy.contains('The data you submitted did not pass our security check.')
        })

        it('Persists tool checkboxes on validation errors', function() {
            

            cy.get('.cke_button__blockquote').click()
            cy.get('.cke_button__filemanager').click()
            cy.get('.cke_button__inserttable').click()

            page.get('tool_set_name').type('<script>Haha')
            page.get('tool_set_save_button').eq(0).click()

            cy.hasNoErrors()

            cy.get('.cke_button__blockquote').should('not.have.class', 'disabled')
            cy.get('.cke_button__filemanager').should('not.have.class', 'disabled')
            cy.get('.cke_button__inserttable').should('not.have.class', 'disabled')
        })
    })

})
