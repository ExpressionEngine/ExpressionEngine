/// <reference types="Cypress" />

/**
 * RTE Redactor Full - Formatting Options Tests
 * 
 * Tests for:
 * - Format dropdown verification on publish edit page
 * - Disabled buttons exclusion tests
 */

import { 
    setupRedactorFullTests, 
    page, 
    navigateToPublishEditPage, 
    navigateToToolsetEditPage,
    saveToolset,
    getRedactorField 
} from '../../support/rte/redactor-full-setup';

context('RTE Toolset Edit Page - Redactor Full - Formatting', () => {

    const toolbarSections = [
        { name: 'Extrabar', toggle: 'toolbar_extrabar', fieldset: 'extrabar_fieldset', configKey: 'extrabar' },
        { name: 'Addbar', toggle: 'toolbar_addbar', fieldset: 'addbar_fieldset', configKey: 'addbar' },
        { name: 'Context bar', toggle: 'toolbar_context', fieldset: 'context_bar_fieldset', configKey: 'context' }
    ]

    setupRedactorFullTests()

    describe('Format Dropdown Integration', function() {
        it('Verifies Formatting options buttons appear in format dropdown on publish edit page', function() {
            // Collect all Formatting options button data-names from config page
            page.get('formatting_options_fieldset').find('a.rx-button').then(($buttons) => {
                const formattingDataNames = []
                
                $buttons.each((index, btn) => {
                    const $btn = Cypress.$(btn)
                    const spanId = $btn.parents('span[id^="tb-option-"]').attr('id')
                    if (spanId) {
                        const btnName = spanId.replace('tb-option-', '')
                        formattingDataNames.push(btnName)
                    }
                })
                
                // Wrap the array as Cypress alias
                cy.wrap(formattingDataNames).as('formattingDataNames')
            })
            
            // Save to ensure changes are persisted
            saveToolset()
            
            // Navigate to publish edit page
            navigateToPublishEditPage()
            
            // Get the stored data names and verify
            cy.get('@formattingDataNames').then((formattingDataNames) => {
                // Find Redactor field and click format button
                getRedactorField().within(() => {
                    // Locate .rx-toolbar
                    cy.get('.rx-toolbar').should('exist')
                    
                    // Find a[data-name="format"] button
                    cy.get('.rx-toolbar').find('a[data-name="format"]').should('exist')
                    
                    // Click the format button to open dropdown
                    cy.get('.rx-toolbar').find('a[data-name="format"]').click()
                })
                
                // Wait for dropdown to appear (dropdown is outside the fieldset)
                cy.wait(300)
                
                // Verify dropdown list is visible (search in whole document, not within fieldset)
                cy.get('.rx-dropdown').should('be.visible')
                
                // Verify all Formatting options buttons are present in the dropdown
                formattingDataNames.forEach(dataName => {
                    cy.get('.rx-dropdown').find(`[data-name="${dataName}"]`).should('exist')
                })
            })
        })
    })

    describe('Disabled Buttons Exclusion', function() {
        it('Disables specific buttons and verifies they are excluded from publish edit page', function() {
            // Ensure all toolbars are enabled first

            toolbarSections.forEach(({ toggle }) => {
                page.get(toggle).then($el => {
                    const wasInitiallyOn = $el.parents('.toggle-btn').hasClass('on')

                    if (!wasInitiallyOn) {
                        page.get(toggle).parent().click()
                        cy.hasNoErrors()
                    }
                })
            })

            // Disable hotkeys_rte in Extrabar
            page.get('extrabar_fieldset').within(() => {
                cy.get('span#tb-option-hotkeys a').then(($btn) => {
                    if (!$btn.hasClass('disable')) {
                        cy.wrap($btn).click()
                        cy.hasNoErrors()
                    }
                })
                cy.get('span#tb-option-hotkeys a')
                    .should('have.class', 'disable')
                    .find('input')
                    .should('have.attr', 'disabled')
            })
            
            // Disable list in Addbar
            page.get('addbar_fieldset').within(() => {
                cy.get('span#tb-option-list a').then(($btn) => {
                    if (!$btn.hasClass('disable')) {
                        cy.wrap($btn).click()
                        cy.hasNoErrors()
                    }
                })
                cy.get('span#tb-option-list a')
                    .should('have.class', 'disable')
                    .find('input')
                    .should('have.attr', 'disabled')
            })
            
            // Disable Bold in Context bar
            page.get('context_bar_fieldset').within(() => {
                cy.get('span#tb-option-bold a').then(($btn) => {
                    if (!$btn.hasClass('disable')) {
                        cy.wrap($btn).click()
                        cy.hasNoErrors()
                    }
                })
                cy.get('span#tb-option-bold a')
                    .should('have.class', 'disable')
                    .find('input')
                    .should('have.attr', 'disabled')
            })
            
            // Disable h1 and h2 in Formatting options
            page.get('formatting_options_fieldset').within(() => {
                cy.get('span#tb-option-h1 a').first().then(($btn) => {
                    if (!$btn.hasClass('disable')) {
                        cy.wrap($btn).click()
                        cy.hasNoErrors()
                    }
                })
                cy.get('span#tb-option-h1 a')
                    .should('have.class', 'disable')
                    .find('input')
                    .should('have.attr', 'disabled')
                
                cy.get('span#tb-option-h2 a').first().then(($btn) => {
                    if (!$btn.hasClass('disable')) {
                        cy.wrap($btn).click()
                        cy.hasNoErrors()
                    }
                })
                cy.get('span#tb-option-h2 a')
                    .should('have.class', 'disable')
                    .find('input')
                    .should('have.attr', 'disabled')
            })
            
            // Save the changes
            saveToolset()
            
            // Navigate to publish edit page
            navigateToPublishEditPage()
            
            // Verify Extrabar does NOT contain hotkeys
            getRedactorField().within(() => {
                cy.get('.rx-extrabar').should('exist')
                cy.get('.rx-extrabar').find('[data-name="hotkeys"]').should('not.exist')
            })
            
            // Verify Addbar dropdown does NOT contain list
            getRedactorField().find('a[data-name="add"]').should('exist').click()
            cy.wait(300)
            // Dropdown is outside fieldset - search in whole document
            cy.get('.rx-dropdown-items').should('be.visible')
            cy.get('.rx-dropdown-items').find('[data-name="list"]').should('not.exist')
            
            // Close the dropdown by clicking outside
            cy.get('body').click(0, 0)
            cy.wait(200)
            
            // Verify Context bar does NOT contain Bold
            // getRedactorField().within(() => {
            //     // Type new text
            //     cy.get('div.rx-content, [contenteditable="true"]').first()
            //         .focus()
            //         .type('{selectall}{del}Test word for selection', { delay: 50 })
            //     cy.wait(300)
            // })
            
            // Use JavaScript to select text and trigger context bar (mouse selection required)
            // getRedactorField().find('div.rx-content, [contenteditable="true"]').first().then($el => {
            //     const el = $el[0]
            //     const range = document.createRange()
            //     const sel = window.getSelection()
                
            //     // Select specific part of the text (often better for triggering context bars)
            //     if (el.firstChild) {
            //         range.setStart(el.firstChild, 0)
            //         range.setEnd(el.firstChild, 4) // Select "Test"
            //         sel.removeAllRanges()
            //         sel.addRange(range)
            //     }
                
            //     // Trigger mouse events in sequence
            //     const mouseEventInit = { bubbles: true, cancelable: true, view: window };
            //     el.dispatchEvent(new MouseEvent('mousedown', mouseEventInit));
            //     el.dispatchEvent(new MouseEvent('mouseup', mouseEventInit));
            //     el.dispatchEvent(new MouseEvent('click', mouseEventInit));
            // })
            
            // // Give it time to render
            // cy.wait(1000)
            
            // // Context bar is outside fieldset
            // cy.get('.rx-context', { timeout: 10000 }).should('be.visible')
            // cy.get('.rx-context').find('[data-name="bold"]').should('not.exist')
            
            // Verify Formatting options dropdown does NOT contain h1 and h2
            getRedactorField().find('.rx-toolbar a[data-name="format"]').click()
            cy.wait(500)
            
            // Dropdown is outside fieldset
            cy.get('.rx-dropdown-items').should('be.visible')
            cy.get('.rx-dropdown-items').find('[data-name="h1"]').should('not.exist')
            cy.get('.rx-dropdown-items').find('[data-name="h2"]').should('not.exist')
        })
    })
})
