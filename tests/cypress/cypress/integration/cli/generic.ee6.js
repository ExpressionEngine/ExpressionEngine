/// <reference types="Cypress" />

context('CLI', () => {

    before(function(){
        //cy.task('db:seed')

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
        })
    })

    it('list all commands', function() {
        cy.exec('php ../../system/ee/eecli.php list').then((result) => {
            expect(result.code).to.eq(0)
            expect(result.stderr).to.be.empty
            expect(result.stdout).to.not.contain('on line')
        })
    })

    it('display help', function() {
        cy.exec('php ../../system/ee/eecli.php cache:clear --help').then((result) => {
            expect(result.code).to.eq(0)
            expect(result.stderr).to.be.empty
            expect(result.stdout).to.not.contain('on line')
            expect(result.stdout).to.contain('SUMMARY')
            expect(result.stdout).to.contain('USAGE')
            expect(result.stdout).to.contain('DESCRIPTION')
            expect(result.stdout).to.contain('SUMMARY')
        })
    })

    describe('clear caches', function() {
        before(function() {
            // turn the caching on
            cy.authVisit('admin.php?/cp/design/manager/cached')
            cy.get('.app-listing__row a').contains('index').click()
            cy.get('.js-tab-button.tab-bar__tab').contains('Settings').click()
            cy.get('[data-toggle-for="cache"]').click()
            cy.get('button').contains('Save').click()
        })

        it('clear caches', function() {
            cy.logout()
            cy.visit('index.php/cached/index')

            cy.readFile('../../system/user/cache/default_site/tag_cache/index.html'); //assert cache exists

            cy.exec('php ../../system/ee/eecli.php cache:clear -t tag').then((result) => {
                expect(result.code).to.eq(0)
                expect(result.stderr).to.be.empty
                expect(result.stdout).to.not.contain('on line')
                expect(result.stdout).to.contain('Tag caches are cleared!')
                cy.readFile('../../system/user/cache/default_site/tag_cache/index.html').should('not.exist')
            })
        })

        after(function() {
            // turn caching off back again
            cy.authVisit('admin.php?/cp/design/manager/cached')
            cy.get('.app-listing__row a').contains('index').click()
            cy.get('.js-tab-button.tab-bar__tab').contains('Settings').click()
            cy.get('[data-toggle-for="cache"]').click()
            cy.get('button').contains('Save').click()
        })

    })

})
