import Stats from '../../elements/pages/utilities/Stats';

const page = new Stats;


context('Content Reindex', () => {

    before(function(){
        cy.task('db:seed')
    })
    
    beforeEach(function() {
        cy.authVisit('admin.php?/cp/utilities/reindex');
        page.get('heading').contains("Content Reindex")
        cy.hasNoErrors();
    })

    it('displays appropriate messages and can reindex content', () => {
        page.get('alert').contains('Reindexing Not Necessary')
        cy.eeConfig({ item: 'search_reindex_needed', value: '1' })
        cy.wait(5000)
        cy.visit('admin.php?/cp/utilities/reindex');
        page.get('alert').should('not.contain', 'Reindexing Not Necessary')
        cy.get('.panel-footer .button.button--primary').first().click()
        cy.wait(10000)
        page.hasAlert('success')
        page.get('alert').contains('Reindexing Not Necessary')
        cy.hasNoErrors();
    })


})