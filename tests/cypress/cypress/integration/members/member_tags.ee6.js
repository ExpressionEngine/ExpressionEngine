/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Member Tags', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })

        //copy templates
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })


    it('exp:member:has_role', function() {
        cy.visit('index.php/mbr/has_role')

        cy.get('p').should('contain', 'Is either Member or SuperAdmin')
        cy.get('p').should('contain', 'Is not Member')
        cy.get('p').should('not.contain', 'Is Member')
    })

})
