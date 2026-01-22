/// <reference types="Cypress" />

/**
 * RTE Redactor Full - Toolbar Configuration Tests
 * 
 * Tests for:
 * - Toolbar section visibility
 * - Button verification against Redactor Full configuration
 * - Toggle switches (sticky, extrabar, addbar, context, control)
 */

import { setupRedactorFullTests, page, saveToolset, verifySectionButtons } from '../../support/rte/redactor-full-setup';

context('RTE Toolset Edit Page - Redactor Full - Toolbar', () => {

    // Get expected buttons from page object model
    const expectedRedactorFull = page.defaultToolbars['Redactor Full']

    // Shared configuration for toolbar sections with toggles
    const toolbarSections = [
        { name: 'Extrabar', toggle: 'toolbar_extrabar', fieldset: 'extrabar_fieldset', configKey: 'extrabar' },
        { name: 'Addbar', toggle: 'toolbar_addbar', fieldset: 'addbar_fieldset', configKey: 'addbar' },
        { name: 'Context bar', toggle: 'toolbar_context', fieldset: 'context_bar_fieldset', configKey: 'context' }
    ]
    setupRedactorFullTests()

    describe('Toolbar Configuration - Button Verification', function() {
        it('Verifies all active buttons match Redactor Full configuration', function() {

            // Verify Main toolbar (editor) buttons
            // page.get('main_toolbar_fieldset').within(() => {
            //     expectedRedactorFull.editor.forEach(buttonName => {
            //         cy.get(`span#tb-option-${buttonName}`)
            //             .should('exist')
            //             .find('a')
            //             .should('not.have.class', 'disable')
            //             .find('input')
            //             .should('not.have.attr', 'disabled')
            //     })
            // })

            // Verify each toolbar section: toggle is ON, fieldset is visible, buttons are active
            toolbarSections.forEach(({ name, toggle, fieldset, configKey }) => {
                // Verify toggle is ON
                page.get(toggle).parents('.toggle-btn')
                    .should('have.class', 'on')
                    .find('input')
                    .should('have.value', 'y')
                
                // Verify fieldset is visible
                page.get(fieldset).should('be.visible')
                
                // Verify all buttons from config are active
                page.get(fieldset).within(() => {
                    expectedRedactorFull[configKey].forEach(buttonName => {
                        cy.get(`span#tb-option-${buttonName}`)
                            .should('exist')
                            .find('a')
                            .should('not.have.class', 'disable')
                            .find('input')
                            .should('not.have.attr', 'disabled')
                    })
                })
            })

            // Verify Formatting options buttons (no toggle, always visible)
            page.get('formatting_options_fieldset').within(() => {
                expectedRedactorFull.format.forEach(buttonName => {
                    cy.get(`span#tb-option-${buttonName}`)
                        .should('exist')
                        .find('a')
                        .should('not.have.class', 'disable')
                        .find('input')
                        .should('not.have.attr', 'disabled')
                })
            })
        })
    })

    describe('Toolbar Configuration - Toggle Switches', function() {
        it('Can toggle Make toolbar sticky switch with persistence', function() {
            page.get('toolbar_sticky').should('exist')
            page.get('toolbar_sticky').then($el => {
                // Check initial state using jQuery methods
                const wasInitiallyOn = $el.parents('.toggle-btn').hasClass('on')
                
                // Toggle the switch
                page.get('toolbar_sticky').parent().click()
                cy.hasNoErrors()
                
                // Verify toggle changed
                page.get('toolbar_sticky').parents('.toggle-btn').then($btn => {
                    expect($btn.hasClass('on')).to.not.equal(wasInitiallyOn)
                })
                
                // Save and verify persistence
                saveToolset()
                
                // Reload and verify
                cy.reload()
                cy.hasNoErrors()
                cy.get('.ee-main__content').should('be.visible')
                
                page.get('toolbar_sticky').parents('.toggle-btn').then($btn => {
                    expect($btn.hasClass('on')).to.not.equal(wasInitiallyOn)
                })
            })
        })

        // Test all toolbar sections with toggles (Extrabar, Addbar, Context bar)
        it('Shows section when enabled and hides when disabled', function() {
            toolbarSections.forEach(({ name, toggle, fieldset }) => {
                page.get(toggle).should('exist')
                page.get(toggle).then($el => {
                    const wasInitiallyOn = $el.parents('.toggle-btn').hasClass('on')
                    
                    // Enable section if not enabled
                    if (!wasInitiallyOn) {
                        page.get(toggle).parent().click()
                        cy.hasNoErrors()
                    }
                    
                    // Verify section is visible
                    page.get(fieldset).should('be.visible')
                    
                    // Verify all buttons exist and are active
                    verifySectionButtons(fieldset)
                    
                    // Save and verify persistence
                    saveToolset()
                    
                    // Reload and verify section is still visible
                    cy.reload()
                    cy.hasNoErrors()
                    cy.get('.ee-main__content').should('be.visible')
                    
                    page.get(fieldset).should('be.visible')
                    page.get(toggle).parents('.toggle-btn').should('have.class', 'on')
                    
                    // Now disable section
                    page.get(toggle).parent().click()
                    cy.hasNoErrors()
                    
                    // Verify section is hidden
                    page.get(fieldset).should('not.be.visible')
                    
                    // Save and verify persistence
                    saveToolset()
                    
                    // Reload and verify section is still hidden
                    cy.reload()
                    cy.hasNoErrors()
                    cy.get('.ee-main__content').should('be.visible')
                    
                    page.get(fieldset).should('not.be.visible')
                    page.get(toggle).parents('.toggle-btn').should('not.have.class', 'on')
                })
            })
        })

        it('Shows Control bar when enabled and hides when disabled with persistence', function() {
            page.get('toolbar_control').should('exist')
            page.get('toolbar_control').then($el => {
                const wasInitiallyOn = $el.parents('.toggle-btn').hasClass('on')
                
                // Enable control bar if not enabled
                if (!wasInitiallyOn) {
                    page.get('toolbar_control').parent().click()
                    cy.hasNoErrors()
                }
                
                // Verify Control bar toggle is on
                page.get('toolbar_control').parents('.toggle-btn').should('have.class', 'on')
                
                // Save and verify persistence
                saveToolset()
                
                // Reload and verify control bar is still enabled
                cy.reload()
                cy.hasNoErrors()
                cy.get('.ee-main__content').should('be.visible')
                
                page.get('toolbar_control').parents('.toggle-btn').should('have.class', 'on')
                
                // Now disable control bar
                page.get('toolbar_control').parent().click()
                cy.hasNoErrors()
                
                // Verify Control bar toggle is off
                page.get('toolbar_control').parents('.toggle-btn').should('not.have.class', 'on')
                
                // Save and verify persistence
                saveToolset()
                
                // Reload and verify control bar is still disabled
                cy.reload()
                cy.hasNoErrors()
                cy.get('.ee-main__content').should('be.visible')
                
                page.get('toolbar_control').parents('.toggle-btn').should('not.have.class', 'on')
            })
        })
    })
})
