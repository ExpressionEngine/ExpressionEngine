/// <reference types="Cypress" />
const { _, $ } = Cypress


context('Channel Entries tag parameters', () => {

    before(function() {
        cy.task('db:seed')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    it('primary_role_id', function() {
        cy.task('db:query', 'UPDATE exp_channel_titles SET author_id=6 WHERE entry_id=1').then(() => {
            cy.authVisit('index.php/entries/params');
            cy.get('#primary_role_id').should('contain', '1 - Getting to Know ExpressionEngine')
            cy.get('#primary_role_id').should('not.contain', '2 - Welcome to the Example Site!')
        })
    })

})
