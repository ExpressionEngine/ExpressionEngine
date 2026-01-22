/// <reference types="Cypress" />

/**
 * Shared setup for Redactor Full toolset tests
 * 
 * Usage:
 * import { setupRedactorFullTests, page } from '../../support/rte/redactor-full-setup'
 * 
 * context('Test Suite', () => {
 *     setupRedactorFullTests()
 *     // your tests here
 * })
 */

import RteEditToolset from '../../elements/pages/addons/RteEditToolset';

export const page = new RteEditToolset;

export function setupRedactorFullTests() {
    before(function() {
        cy.task('db:seed')
        // Toolsets are now included in database_7.0.0.sql
        // No need to load separate tool_sets.sql
        
        // Fix base_path for local environment (database has CI path hardcoded)
        cy.eeConfig({ item: 'base_path', value: Cypress.env('CYPRESS_BASE_PATH') || '' })
    })

    beforeEach(function() {
        cy.authVisit('admin.php?/cp/addons/settings/rte/edit_toolset&toolset_id=4')
        cy.hasNoErrors()
        cy.get('.ee-main__content').should('be.visible') // Wait for page to fully load
    })
}

/**
 * Navigate to the publish edit page with entry 3 (About the Label - has Redactor field)
 */
export function navigateToPublishEditPage() {
    cy.authVisit('admin.php?/cp/publish/edit/entry/3')
    cy.hasNoErrors()
    cy.get('.ee-main__content').should('be.visible')
}

/**
 * Navigate back to the Redactor Full toolset edit page
 */
export function navigateToToolsetEditPage() {
    cy.authVisit('admin.php?/cp/addons/settings/rte/edit_toolset&toolset_id=4')
    cy.hasNoErrors()
    cy.get('.ee-main__content').should('be.visible')
}

/**
 * Save the toolset and verify success
 */
export function saveToolset() {
    page.get('save_button').click()
    cy.hasNoErrors()
    page.hasAlert('success')
}

/**
 *Verify all section buttons exist and are active
 */
export function verifySectionButtons($section) {
    page.get($section).within(() => {
        cy.get('span[id^="tb-option-"]')
            .should('exist')
            .find('a')
            .should('not.have.class', 'disable')
            .find('input')
            .should('not.have.attr', 'disabled')
    })
}

/**
 * Get the Redactor field container
 */
export function getRedactorField() {
    return cy.contains('label', 'Redactor').parents('fieldset')
}

/**
 * Assert that a toolbar button is active (not disabled)
 * - The <a> element should NOT have class 'disabled'
 * - The <input> inside should NOT have the 'disabled' attribute
 * 
 * @param {string} selector - CSS selector for the button (a element)
 */
export function assertButtonActive(selector) {
    cy.get(selector)
        .should('exist')
        .and('not.have.class', 'disabled')
        .find('input')
        .should('not.have.attr', 'disabled')
}

/**
 * Assert that a toolbar button is disabled
 * - The <a> element should have class 'disabled'
 * - The <input> inside should have the 'disabled' attribute
 * 
 * @param {string} selector - CSS selector for the button (a element)
 */
export function assertButtonDisabled(selector) {
    cy.get(selector)
        .should('exist')
        .and('have.class', 'disabled')
        .find('input')
        .should('have.attr', 'disabled')
}
