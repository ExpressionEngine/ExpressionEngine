/// <reference types="Cypress" />

import Homepage from '../../elements/pages/Homepage';
const page = new Homepage;

context('CP Homepage', () => {

    before(() => {
        cy.task('db:seed')
    })

    beforeEach(() => {
        cy.authVisit(page.url);
    })

    afterEach(() => {
        cy.hasNoErrors();
    })

    it('uses protected native navigation for external links', function() {
        cy.get('a[rel~="external"]').each(($link) => {
            cy.wrap($link).should(($link) => {
                expect($link).to.have.attr('target', '_blank')
                expect($link.attr('rel').split(/\s+/)).to.include.members([
                    'external',
                    'noopener',
                    'noreferrer'
                ])
            })
        })

        cy.get('body').then(($body) => {
            let wasPrevented
            const $dynamicLink = Cypress.$('<a href="https://example.com" rel="external">Test external link</a>')
            const observeDefault = (event) => {
                wasPrevented = event.defaultPrevented
                event.preventDefault()
            }

            $body[0].addEventListener('click', observeDefault)
            $body.append($dynamicLink)

            // The injected link is outside the CP's scrollable content.
            cy.wrap($dynamicLink).click({ force: true }).then(() => {
                expect(wasPrevented).to.be.false
                // Cypress intercepts target property assignments during clicks.
                expect($dynamicLink).to.have.prop('target', '_blank')
                expect($dynamicLink.attr('rel').split(/\s+/)).to.include.members([
                    'external',
                    'noopener',
                    'noreferrer'
                ])

                $body[0].removeEventListener('click', observeDefault)
                $dynamicLink.remove()
            })
        })
    })

    describe('when spam module is not installed', function() {
        beforeEach(function() {
            page.toggleSpam('off')
        })

        it('does not show flagged comments', function() {
            page.get('dashboard').find('.dashboard__item').contains("flagged as spam").should('not.exist')
        })
    })

    describe('when spam module is installed', function() {
        beforeEach(function() {
            page.toggleSpam('on')
        })

        it('shows flagged comments', function() {
            page.get('dashboard').find('.dashboard__item').contains("flagged as spam")
        })
    })
})
