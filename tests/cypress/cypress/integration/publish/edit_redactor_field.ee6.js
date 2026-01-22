/// <reference types="Cypress" />

import Edit from '../../elements/pages/publish/Edit';

const page = new Edit;

context('Publish Page - Edit Entry with Redactor Field', () => {
    before(function(){
        cy.task('db:seed')
    })

    beforeEach(function(){
        // Entry 3 = "About the Label" in About channel, has Redactor field
        cy.authVisit('admin.php?/cp/publish/edit/entry/3');
        cy.hasNoErrors()
        cy.get('.ee-main__content').should('be.visible')
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
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            editor.clear().type('Test content')
        })

        it('Can apply Bold formatting', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            // Select text in the paragraph
            editor.find('p').type('{selectall}')
            
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
            
            editor.find('p').type('{selectall}')
            cy.get('a[data-name="italic"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<em|<i/)
            })
        })

        it('Can apply Deleted/Strikethrough formatting', function() {
            const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
            const editor = fieldset.find('.rx-content')
            
            editor.find('p').type('{selectall}')
            cy.get('a[data-name="deleted"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<del|<s/)
            })
        })

        // it('Can insert links', function() {
        //     const fieldset = cy.get('label:contains("Redactor")').parents('fieldset')
        //     const editor = fieldset.find('.rx-content')
            
        //     editor.clear().type('Link text')
        //     editor.find('p').type('{selectall}')
            
        //     cy.get('a[data-name="link"]').click()
        //     cy.hasNoErrors()
            
        //     // Link dialog should appear or link should be inserted
        //     cy.get('body').then($body => {
        //         if ($body.find('.rx-modal').length > 0) {
        //             cy.get('.rx-modal').should('be.visible')
        //         } else {
        //             editor.invoke('html').then((html) => {
        //                 expect(html).to.include('<a')
        //             })
        //         }
        //     })
        // })

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
            
            editor.clear().type('Formatted text')
            editor.find('p').type('{selectall}')
            
            cy.get('a[data-name="bold"]').click()
            cy.wait(200)
            cy.get('a[data-name="italic"]').click()
            cy.hasNoErrors()
            
            editor.invoke('html').then((html) => {
                expect(html).to.match(/<strong|<b/).and.match(/<em|<i/)
            })
        })
    })
})
