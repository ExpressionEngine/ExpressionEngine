/// <reference types="Cypress" />

import TemplateRoutes from '../../elements/pages/design/TemplateRoutes';
const page = new TemplateRoutes;
const { _, $ } = Cypress

context('Template Generator', () => {

    before(function() {
        cy.task('db:seed')
    })

    beforeEach(function() {
        cy.authVisit('admin.php?/cp/design/generator');
    })

    it('Validates template group name', function() {
        cy.get('input[name="channel:entries[template_group]"]').clear().type('news').blur()
        cy.get('input[name="channel:entries[template_group]"]').parent().find('.ee-form-error-message').should('exist')
        cy.get('input[name="channel:entries[template_group]"]').parent().find('.ee-form-error-message').should('contain', 'The template group name you submitted is already taken')
        
        cy.get('input[name="channel:entries[template_group]"]').clear().type('news1').blur()
        cy.get('input[name="channel:entries[template_group]"]').parent().find('.ee-form-error-message').should('not.exist')
    })

    it('Channel parameter is required', function() {
        cy.get('input[name="channel:entries[template_group]"]').clear().type('news1').blur()
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        page.hasAlert('error')
        page.get('alert').contains("We were unable to create this group, please review and fix errors below")
        cy.get('input[name="channel:entries[channel][]"]').parents('.field-control').find('.ee-form-error-message').should('exist')
        cy.get('input[name="channel:entries[channel][]"]').parents('.field-control').parent().find('.ee-form-error-message').should('contain', 'This field is required')
    })

    it('Actually generates the templates', function() {
        cy.get('input[name="channel:entries[template_group]"]').clear().type('news1').blur()
        cy.get('input[type=checkbox][name="channel:entries[channel][]"]').first().check()
        cy.get('body').type('{ctrl}', {release: false}).type('s')

        page.hasAlert('success')
        page.get('alert').contains("Templates were generated successfully")

        cy.get('.sidebar__link.active').should('contain', 'news1')
        cy.get('.app-listing__row').should('contain', 'entry')
        cy.get('.app-listing__row').should('contain', 'index')

        cy.get('.app-listing__row a:contains("entry")').first().click()
        cy.hasNoErrors()
        cy.get('.CodeMirror-code').should('contain', 'channel:entries')
        cy.get('.CodeMirror-code').type('{pagedown}{pagedown}{pagedown}')
        cy.get('.CodeMirror-code').should('contain', '</body>')
    })


})
