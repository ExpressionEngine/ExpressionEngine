/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';
import { setupRedactorFixture, navigateToPublishEditPage } from '../../support/rte/redactor-full-setup';

const page = new Edit;

function selectTextInEditor(editor) {
    editor.find('p').first().then(($p) => {
        const el = $p[0]
        const textNode = el.firstChild || el

        cy.window().then((win) => {
            const range = win.document.createRange()
            const sel = win.getSelection()
            const len = (textNode.textContent || '').length

            range.setStart(textNode, 0)
            range.setEnd(textNode, len)
            sel.removeAllRanges()
            sel.addRange(range)

            cy.wrap($p).trigger('mouseup', { force: true })
        })
    })
}

context('Publish Page - Edit Entry with Redactor Field', () => {
    setupRedactorFixture()

    beforeEach(function(){
        navigateToPublishEditPage()
    })

    describe('Page Load and Redactor Field Display', function() {
        it('Check Page Load and Redactor Field Display', function() {
            cy.title().should('include', 'Edit Entry')
            page.get('title').should('exist')

            cy.contains('Redactor').should('exist')
            cy.contains('Redactor').should('be.visible')

            // Redactor editor is typically in a textarea that gets replaced
            cy.get('label:contains("Redactor")').parents('fieldset').find('textarea').should('exist')
            // Check for Redactor editor wrapper
            cy.get('label:contains("Redactor")').parents('fieldset').find('[class*="redactor"]').should('exist')

            // The toolbar is likely outside the fieldset or needs a global search
            cy.get('a[data-name="html"]').should('exist')
            cy.get('a[data-name="format"]').should('exist')
            cy.get('a[data-name="alignment"]').should('exist')
            cy.get('a[data-name="bold"]').should('exist')
            cy.get('a[data-name="italic"]').should('exist')
            cy.get('a[data-name="deleted"]').should('exist')
            cy.get('a[data-name="moreinline"]').should('exist')
            cy.get('a[data-name="list"]').should('exist')
            cy.get('a[data-name="link"]').should('exist')
            cy.get('a[data-name="table"]').should('exist')
            cy.get('a[data-name="filebrowser"]').should('exist')
        })
    })

    describe('Toolbar Functionality', function() {
        beforeEach(function() {
            // Clear field before each test
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset');
            fieldset.find('.rx-content')
                .should('be.visible')
                .and('not.be.disabled')
                .clear()
                .type('Test content');
                    })

        it('Can apply Bold formatting', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            // Select text in the paragraph
            cy.wait(150)
            selectTextInEditor(editor)
            
            // Click Bold button
            cy.get('a[data-name="bold"]').click()
            cy.hasNoErrors()
            
            // Verify formatting applied (check for bold tags in HTML)
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<strong|<b/)
            })
        })

        it('Can apply Italic formatting', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            cy.wait(150)
            selectTextInEditor(editor)
            cy.get('a[data-name="italic"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<em|<i/)
            })
        })

        it('Can apply Deleted/Strikethrough formatting', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            cy.wait(150)
            selectTextInEditor(editor)
            cy.get('a[data-name="deleted"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<del|<s/)
            })
        })

        it('Can insert links via dropdown', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset');
            const editor = fieldset.find('.rx-content');

            editor.should('be.visible')
                  .and('not.be.disabled')
                  .clear()
                  .type('Link');
            cy.wait(150)
            selectTextInEditor(editor)

            cy.get('a[data-name="link"]').click();

            cy.get('.rx-dropdown', { timeout: 10000 })
                .should('be.visible')
                .within(() => {
                    cy.get('input.rx-form-input[name="url"]')
                        .clear()
                        .type('http://google.com/');

                    cy.get('input.rx-form-input[name="text"]')
                        .should('have.value', 'Link');

                    cy.get('button[name="insert"]').click();
                });

            editor.should('contain.html', '<a href="http://google.com/">Link</a>');
        });


        it('Can toggle HTML source view', function() {
            cy.get('a[data-name="html"]').click()
            cy.hasNoErrors()
            
            // HTML view should be visible
            cy.get('.rx-source-container').should('be.visible')
            cy.hasNoErrors()
        })
    })

    describe('Content Entry and Formatting', function() {
        it('Can enter plain text', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            editor.clear().type('This is plain text content')
            editor.should('contain.text', 'This is plain text content')
        })

        it('Can apply multiple formatting (bold and italic)', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            editor.should('be.visible')
                  .and('not.be.disabled')
                  .clear()
                  .type('Test content');
            cy.wait(150)
            selectTextInEditor(editor)
            
            cy.get('.rx-toolbox-container a[data-name="bold"]').click()
            cy.wait(200)
            cy.get('.rx-toolbox-container a[data-name="italic"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<strong|<b/).and.match(/<em|<i/)
            })
        })
    })
})
