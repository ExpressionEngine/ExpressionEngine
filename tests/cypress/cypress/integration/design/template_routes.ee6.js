/// <reference types="Cypress" />

import TemplateRoutes from '../../elements/pages/design/TemplateRoutes';
import TemplatePartialForm from '../../elements/pages/design/TemplatePartialForm';
const page = new TemplateRoutes;
const form = new TemplatePartialForm;
const { _, $ } = Cypress

context('Template Routes', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit(page.url);
    })

    it('displays', function() {
        //page.get('new_route_button').should('exist')
        cy.get('a').contains('New route').should('exist')
        cy.get('[value="Update"]').should('exist')
        page.get('no_results').should('exist')
    })

    it('can add a new route', function() {
        cy.viewport(1280, 660)

        page.add_route({
            template: 'about/index',
            route: 'foo/bar'
        })

        //cy.get('i').filter(':visible').contains('Choose wisely').first().click()
        //cy.get('.select--open .select__dropdown-item span').contains('/').first().click()
        //cy.wait(300)
        page.get('update_button').filter(':visible').first().click()

        cy.hasNoErrors()

        page.hasAlert()
        page.get('alert').contains('Template Routes Saved')
        page.get('routes').its('length').should('eq', 1)
        page.get('routes').eq(0).find('td:nth-child(1)').contains('index')
        page.get('routes').eq(0).find('td:nth-child(2)').contains('about')
        page.get('routes').eq(0).find('td:nth-child(3) input').should('have.value', 'foo/bar')
        page.get('routes').eq(0).find('td:nth-child(4) [data-toggle-for=required]').should('have.class', 'off')
    })

    it('can edit a route', function() {

        page.get('routes').eq(0).find('td:nth-child(3) input').clear().type('cypress/edited')

        page.get('update_button').first().click({force:true})

        page.get('routes').eq(0).find('td:nth-child(3) input').should('have.value', 'cypress/edited')
    })

    it('can reorder routes', function() {
        page.add_route({
            template: 'about/404',
            route: 'boo/far'
        })
        page.get('update_button').filter(':visible').first().click({force:true}).then(function() {

            page.get('routes').its('length').should('eq', 2)

            let first = page.$('routes').eq(0).find('td:nth-child(3) input').val()
            let second = page.$('routes').eq(1).find('td:nth-child(3) input').val()

            page.get('routes').eq(0).find('.js-grid-reorder-handle').then(function(target) {
                page.get('routes').eq(1).find('.js-grid-reorder-handle').dragTo(target)
            })

            page.get('update_button').first().click({force:true})

            page.get('routes').eq(0).find('td:nth-child(3) input').should('have.value', second)
            page.get('routes').eq(1).find('td:nth-child(3) input').should('have.value', first)
        })
    })

    it('can remove a route', function() {

        page.get('update_button').click({force:true})

        page.get('routes').its('length').should('eq', 2)

        page.get('routes').eq(0).find('td:nth-child(5) [rel=remove_row]').click()
        page.get('update_button').click()
        page.get('routes').its('length').should('eq', 1)
        page.get('routes').eq(0).find('td:nth-child(3) input').should('have.value', 'cypress/edited')
    })
})
