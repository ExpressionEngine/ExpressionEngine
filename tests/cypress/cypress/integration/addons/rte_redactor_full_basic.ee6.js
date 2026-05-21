/// <reference types="Cypress" />

/**
 * RTE Redactor Full - Basic Tests
 * 
 * Tests for:
 * - Page Load and Display
 * - Basic Settings Section
 * - Plugins Section
 * - Advanced Settings
 */

import { setupRedactorFullTests, page, saveToolset, assertButtonActive, assertButtonDisabled, navigateToPublishEditPage, navigateToToolsetEditPage } from '../../support/rte/redactor-full-setup';

context('RTE Toolset Edit Page - Redactor Full - Basic', () => {

    setupRedactorFullTests()

    describe('Basic Settings Section', function() {
        it('Shows Tool set Name field with correct value', function() {
            page.get('toolset_name').should('exist')
            page.get('toolset_name').should('be.visible')
            page.get('toolset_name').invoke('val').should('eq', 'Redactor Full')
        })

        it('Shows Editor Type dropdown with Redactor selected', function() {
            page.get('toolset_type').should('exist')
            page.get('toolset_type').should('be.visible')
            page.get('toolset_type').should('contain', 'Redactor')
            page.get('toolset_type').find('option:selected').should('contain', 'Redactor')
            page.get('toolset_type').should('not.contain', 'RedactorX')
            page.get('toolset_type').should('not.contain', 'Redactor Classic')
        })

        it('Shows Upload Directory dropdown', function() {
            page.get('upload_dir').should('exist')
            page.get('upload_dir').should('be.visible')
        })

        it('Shows Text direction radio buttons with Left to right selected', function() {
            page.get('text_direction_ltr').should('exist')
            page.get('text_direction_rtl').should('exist')
            page.get('text_direction_ltr').should('be.checked')
            page.get('text_direction_rtl').should('not.be.checked')
        })
    })

    describe('Plugins Section', function() {
        it('Verifies plugins match Redactor Full configuration and are all active', function() {
            const expectedPlugins = page.defaultToolbars['Redactor Full'].plugins

            cy.contains('Plugins').should('exist')

            page.get('plugins_fieldset').within(() => {
                expectedPlugins.forEach(pluginName => {
                    cy.get(`span#tb-option-${pluginName}`)
                        .should('exist')
                        .and('be.visible')
                        .find('a')
                        .should('not.have.class', 'disable')
                        .find('input')
                        .should('not.have.attr', 'disabled')
                })
            })
        })

        it('Disables first 2 plugin buttons and verifies they persist after save', function() {
            const expectedPlugins = page.defaultToolbars['Redactor Full'].plugins
            const pluginsToDisable = expectedPlugins.slice(0, 2)

            // Click on first 2 plugin buttons to disable them
            page.get('plugins_fieldset').within(() => {
                pluginsToDisable.forEach(pluginName => {
                    // Verify button is active before clicking
                    cy.get(`span#tb-option-${pluginName}`)
                        .find('a')
                        .should('not.have.class', 'disable')
                        .find('input')
                        .should('not.have.attr', 'disabled')
                    
                    // Click to disable
                    cy.get(`span#tb-option-${pluginName}`).find('a').click()
                    cy.hasNoErrors()
                    
                    // Verify button is now disabled
                    cy.get(`span#tb-option-${pluginName}`)
                        .find('a')
                        .should('have.class', 'disable')
                        .find('input')
                        .should('have.attr', 'disabled')
                })
            })

            // Save the page
            saveToolset()

            // Reload and verify buttons are still disabled
            cy.reload()
            cy.hasNoErrors()
            cy.get('.ee-main__content').should('be.visible')

            page.get('plugins_fieldset').within(() => {
                pluginsToDisable.forEach(pluginName => {
                    cy.get(`span#tb-option-${pluginName}`)
                        .find('a')
                        .should('have.class', 'disable')
                        .find('input')
                        .should('have.attr', 'disabled')
                })
            })

            // Restore original state - re-enable the buttons
            page.get('plugins_fieldset').within(() => {
                pluginsToDisable.forEach(pluginName => {
                    cy.get(`span#tb-option-${pluginName}`).find('a').click()
                    cy.hasNoErrors()
                })
            })

            saveToolset()
        })
    })

    describe('Advanced Settings', function() {
        it('Shows Custom Stylesheet field', function() {
            cy.contains('Custom Stylesheet').should('exist')
            cy.contains('CSS template with styles').should('exist')
        })

        it('Shows Minimal height field with value 200 and applies to .rx-content on entry page', function() {
            // Check the field value on toolset page
            page.get('minimal_height').should('exist')
            page.get('minimal_height').invoke('val').should('eq', '200')

            // Navigate to entry page and verify .rx-content has min-height: 200px
            navigateToPublishEditPage()
            cy.get('.rx-content')
                .should('exist')
                .and('have.css', 'min-height', '200px')

            // Navigate back to toolset edit page for subsequent tests
            navigateToToolsetEditPage()
        })

        it('Shows Maximal height field', function() {
            page.get('maximal_height').should('exist')
            page.get('maximal_height').should('be.visible')
        })

        it('Can toggle Advanced configuration switch', function() {
            page.get('advanced_config').should('exist')
            const initialValue = page.get('advanced_config').then($el => $el.is(':checked'))
            
            page.get('advanced_config').parent().click()
            cy.hasNoErrors()
            
            page.get('advanced_config').should(($el) => {
                expect($el.is(':checked')).to.not.equal(initialValue)
            })
        })
    })
})
