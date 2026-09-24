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

export function setupRedactorFixture() {
    before(function() {
        cy.task('db:seed')
        cy.task('db:load', '../../support/sql/rte-settings/redactor_test_channel.sql')

        cy.task(
            'db:query',
            `SELECT ct.entry_id
             FROM exp_channel_titles ct
             INNER JOIN exp_channels c ON c.channel_id = ct.channel_id
             WHERE c.channel_name = 'redactor_test'
               AND ct.url_title = 'redactor_test_entry'
             ORDER BY ct.entry_id DESC
             LIMIT 1`
        ).then(([rows]) => {
            expect(rows, 'redactor fixture query result').to.have.length.greaterThan(0)
            expect(rows[0].entry_id, 'redactor fixture entry_id').to.exist
            Cypress.env('redactor_fixture_entry_id', rows[0].entry_id)
        })
        
        // Fix base_path for local environment (database has CI path hardcoded)
        cy.eeConfig({ item: 'base_path', value: Cypress.env('CYPRESS_BASE_PATH') || '' })
    })
}

export function setupRedactorFullTests() {
    setupRedactorFixture()

    beforeEach(function() {
        cy.authVisit('admin.php?/cp/addons/settings/rte/edit_toolset&toolset_id=4')
        cy.hasNoErrors()
        cy.get('.ee-main__content').should('be.visible') // Wait for page to fully load
    })
}

function initializeRedactorOnly() {
    cy.contains('label', 'Redactor')
        .parents('fieldset')
        .as('redactorField')
        .should('exist');

    cy.get('@redactorField').then(($fieldset) => {
        if ($fieldset.find('.rx-container').length > 0) {
            cy.log('Redactor already initialized');
            return;
        }

        const $textarea = $fieldset.find('textarea.rte-textarea').first();
        const fieldId = $textarea.attr('id');
        const configHandle = $textarea.data('config');

        expect(fieldId, 'Textarea ID').to.be.a('string').and.not.be.empty;

        cy.window({ timeout: 10000 }).then((win) => {
            expect(win.Rte, 'Rte constructor').to.be.a('function');
            
            new win.Rte(fieldId, configHandle, false);
        });
    });

    cy.get('@redactorField')
        .find('.rx-container', { timeout: 15000 })
        .should('be.visible');
}


/**
 * Navigate to the publish edit page with the runtime Redactor fixture entry.
 */
export function navigateToPublishEditPage() {
    const fixtureEntryId = Cypress.env('redactor_fixture_entry_id')
    expect(fixtureEntryId, 'redactor_fixture_entry_id').to.exist

    cy.on('uncaught:exception', (err) => {
        if (err && typeof err.message === 'string' && err.message.includes('Redactor is not defined')) {
            return false
        }
    })

    cy.authVisit(`admin.php?/cp/publish/edit/entry/${fixtureEntryId}`)
    cy.hasNoErrors()
    cy.get('.ee-main__content').should('be.visible')
    cy.wait(5000)
    initializeRedactorOnly()
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
