/// <reference types="Cypress" />

const { _, $ } = Cypress

context('Member Registration', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('db:load', '../../support/sql/more-roles.sql')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })
        cy.eeConfig({ item: 'allow_member_registration', value: 'y' })
        cy.eeConfig({ item: 'req_mbr_activation', value: 'none' })

        //copy templates
        cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/default_site/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
            cy.clearCookies()
        })
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/mbr.group')
    })

    beforeEach(function() {
        //cy.clearCookies()
    })

    afterEach(function() {
        //cy.clearCookies()
    })

    context('regular signup', function() {

        it('registers normally', function() {
            cy.clearCookies()
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user1');
            cy.get('#email').clear().type('user1@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            /*cy.get('#username').invoke('text').then((text) => {
                expect(text).equal('user1')
            })*/
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user1')").parents('tr').find('td:nth-child(4)').contains('Members')
        })

        it('registers into unlocked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '6' })
            
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user2');
            cy.get('#email').clear().type('user2@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            /*cy.get('#username').invoke('text').then((text) => {
                expect(text).equal('user2')
            })*/
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user2')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into locked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '7' })
            
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user3');
            cy.get('#email').clear().type('user3@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user3')").should('not.exist')
        })

        it('registers into selected unlocked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })
            
            cy.visit('index.php/mbr/register/6');
            cy.get('#username').clear().type('user4');
            cy.get('#email').clear().type('user4@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            /*cy.get('#username').invoke('text').then((text) => {
                expect(text).equal('user4')
            })*/
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user4')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into selected locked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })
            
            cy.visit('index.php/mbr/register/7');
            cy.get('#username').clear().type('user5');
            cy.get('#email').clear().type('user5@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user5')").should('not.exist')
        })

        it.only('cannot register into non-existing group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })
            
            cy.visit('index.php/mbr/register/143');
            cy.get('#username').clear().type('user6');
            cy.get('#email').clear().type('user6@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role does not exist')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user6')").should('not.exist')
        })

    })

})
