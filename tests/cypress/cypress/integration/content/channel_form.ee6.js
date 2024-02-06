/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Channel Form', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    it('Check basic form attributes', function() {
        cy.authVisit('index.php/entries/channel-form-basic');
        cy.get('form').should('have.attr', 'id', 'form-id')
        cy.get('form').invoke('attr', 'class').should('include', 'ee-cform').should('include', 'form-class')
        cy.get('form').should('have.attr', 'title', 'Channel Form')
        cy.get('form').should('have.attr', 'onMouseOver', '[removed]void(0)') //XSS filtering applied
        cy.get('form').should('have.attr', 'data-title', 'Form')
        cy.get('form').should('have.attr', 'aria-label', 'Cypress Test')
        cy.get('form').should('not.have.attr', 'unsupported_param')
        cy.get('form').should('not.have.attr', 'data-<b>xss</b>')
        cy.get('form').should('have.attr', 'data-xss', 'danger')
    })

})
