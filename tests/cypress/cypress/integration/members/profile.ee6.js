/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Member Profile', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    context('Forgot Password', () => {
        it('Check forgot password form attributes', function() {
            cy.clearCookies()
            cy.visit('index.php/members/forgot-password');
            cy.get('form').should('have.attr', 'class', 'member-forgot')
            cy.get('form').should('have.attr', 'title', 'Forgot Password Form')
            cy.get('form').should('have.attr', 'onMouseOver', '[removed]void(0)') //XSS filtering applied
            cy.get('form').should('have.attr', 'data-title', 'Form')
            cy.get('form').should('have.attr', 'aria-label', 'Cypress Test')
            cy.get('form').should('not.have.attr', 'unsupported_param')
            cy.get('form').should('not.have.attr', 'data-<b>xss</b>')
            cy.get('form').should('have.attr', 'data-xss', 'danger')
        })
    
    })
    
})
