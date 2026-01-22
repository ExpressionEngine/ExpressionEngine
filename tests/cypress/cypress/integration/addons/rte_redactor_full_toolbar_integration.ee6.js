/// <reference types="Cypress" />

/**
 * RTE Redactor Full - Toolbar Integration Tests
 * 
 * Tests that navigate between config page and publish edit page to verify:
 * - Main toolbar buttons appear in .rx-toolbar-0
 * - Extrabar buttons appear in .rx-extrabar
 * - Addbar buttons appear in .rx-dropdown-items
 * - Context bar buttons appear in .rx-context
 * - Sticky class is applied/removed based on toggle
 */

import { 
    setupRedactorFullTests, 
    page, 
    navigateToPublishEditPage, 
    navigateToToolsetEditPage,
    saveToolset,
    getRedactorField 
} from '../../support/rte/redactor-full-setup';

// Helper: Collect all button data-names from a fieldset
function collectButtonDataNames(fieldsetSelector) {
    const dataNames = []
    return cy.get(fieldsetSelector).within(() => {
        cy.get('a[data-name]').each(($btn) => {
            const dataName = $btn.attr('data-name')
            if (dataName) {
                dataNames.push(dataName)
            }
        })
    }).then(() => dataNames)
}

// Helper: Ensure toggle is enabled
function ensureToggleEnabled(toggleSelector) {
    return page.get(toggleSelector).then($el => {
        const wasInitiallyOn = $el.parents('.toggle-btn').hasClass('on')
        if (!wasInitiallyOn) {
            page.get(toggleSelector).parent().click()
            cy.hasNoErrors()
        }
        return wasInitiallyOn
    })
}

// Helper: Type text and select it in the editor
function typeAndSelectText() {
    getRedactorField().find('div.rx-content, [contenteditable="true"]').first().then($el => {
        const el = $el[0]
        
        // Focus and ensure content
        el.focus()
        el.innerHTML = 'Test word for selection'
        
        const range = document.createRange()
        const sel = window.getSelection()
        
        // Select text content
        if (el.firstChild) {
            range.setStart(el.firstChild, 0)
            range.setEnd(el.firstChild, el.firstChild.length)
            sel.removeAllRanges()
            sel.addRange(range)
        }
        
        // Trigger events RedactorX listens to
        $el.trigger('mousedown')
        $el.trigger('mouseup')
        $el.trigger('click')
    })
    cy.wait(1000)
}

// Helper: Verify buttons exist in container on publish page
function verifyButtonsInContainer(containerSelector, dataNames) {
    getRedactorField().within(() => {
        cy.get(containerSelector).should('exist')
        dataNames.forEach(dataName => {
            cy.get(containerSelector).find(`[data-name="${dataName}"]`).should('exist')
        })
    })
}

// Helper: Verify container does not exist on publish page
function verifyContainerNotExist(containerSelector) {
    getRedactorField().within(() => {
        cy.get(containerSelector).should('not.exist')
    })
}

// Helper: Toggle off, save, verify hidden on publish
function toggleOffAndVerify(toggleSelector, fieldsetSelector, containerSelector) {
    page.get(toggleSelector).then($el => {
        if ($el.parents('.toggle-btn').hasClass('on')) {
            page.get(toggleSelector).parent().click()
            cy.hasNoErrors()
        }
    })
    page.get(fieldsetSelector).should('not.be.visible')
    
    saveToolset()
    navigateToPublishEditPage()
    verifyContainerNotExist(containerSelector)
    
    navigateToToolsetEditPage()
}

// Helper: Toggle on, save, verify visible on publish
function toggleOnAndVerify(toggleSelector, fieldsetSelector, containerSelector) {
    page.get(toggleSelector).then($el => {
        if (!$el.parents('.toggle-btn').hasClass('on')) {
            page.get(toggleSelector).parent().click()
            cy.hasNoErrors()
        }
    })
    page.get(fieldsetSelector).should('be.visible')
    
    saveToolset()
    navigateToPublishEditPage()
    
    getRedactorField().within(() => {
        cy.get(containerSelector).should('exist')
    })
    
    navigateToToolsetEditPage()
}

context('RTE Toolset Edit Page - Redactor Full - Toolbar Integration', () => {

    setupRedactorFullTests()

    // doesn't work
    describe('Main Toolbar Integration', function() {
        it('Verifies Main toolbar buttons appear in Redactor field on publish edit page', function() {
            collectButtonDataNames('[id*="hide"]').then((dataNames) => {
                // Use page object if available
                page.get('main_toolbar_fieldset').within(() => {
                    cy.get('a.rx-button').then(($buttons) => {
                        const mainToolbarDataNames = []

                        $buttons.each((i, btn) => {
                            const $btn = Cypress.$(btn)
                            const spanId = $btn.parents('span[id^="tb-option-"]').attr('id')
                           if (spanId) {
                                const name = spanId.replace('tb-option-', '')
                                mainToolbarDataNames.push(name)
                            }
                        })
                        
                        navigateToPublishEditPage()
                        verifyButtonsInContainer('.rx-toolbar-0', mainToolbarDataNames)
                    })
                })
            })
        })
    })

    describe('Sticky Toolbar Integration', function() {
        it('Verifies sticky class on publish edit page based on toggle', function() {
            page.get('toolbar_sticky').then($el => {
                const initialValue = $el.parents('.toggle-btn').hasClass('on')

                // Enable if not already
                if (!initialValue) {
                    page.get('toolbar_sticky').parent().click()
                    cy.hasNoErrors()
                }

                saveToolset()
                navigateToPublishEditPage()
                
                // Dropdown/bars can be outside fieldset
                cy.get('.rx-toolbox-container').should('have.class', 'rx-sticky')
                
                // Disable sticky
                navigateToToolsetEditPage()
                page.get('toolbar_sticky').parent().click()
                cy.hasNoErrors()
                
                saveToolset()
                navigateToPublishEditPage()
                
                cy.get('.rx-toolbox-container').should('not.have.class', 'rx-sticky')
            })
        })
    })

    describe('Extrabar Integration', function() {
        it('Verifies Extrabar on publish edit page and hide when disabled', function() {
            toggleOffAndVerify('toolbar_extrabar', 'extrabar_fieldset', '.rx-extrabar')
            toggleOnAndVerify('toolbar_extrabar', 'extrabar_fieldset', '.rx-extrabar')
        })
    })

    describe('Addbar Integration', function() {
        it('Verifies Addbar on publish edit page and hide when disabled', function() {
            toggleOffAndVerify('toolbar_addbar', 'addbar_fieldset', 'a[data-name="add"]')
            toggleOnAndVerify('toolbar_addbar', 'addbar_fieldset', 'a[data-name="add"]')
        })
    })

    describe('Context Bar Integration', function() {
        it('Verifies Context bar on publish edit page and hide when disabled', function() {
            toggleOffAndVerify('toolbar_context', 'context_bar_fieldset', '.rx-context')
            // For context bar, we need to select text after toggling on to verify it exists
            page.get('toolbar_context').then($el => {
                if (!$el.parents('.toggle-btn').hasClass('on')) {
                    page.get('toolbar_context').parent().click()
                    cy.hasNoErrors()
                }
            })
            saveToolset()
            navigateToPublishEditPage()
            typeAndSelectText()
            cy.get('.rx-context').should('be.visible')
            navigateToToolsetEditPage()
        })
    })
})
