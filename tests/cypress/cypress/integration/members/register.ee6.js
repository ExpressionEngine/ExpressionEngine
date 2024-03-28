/// <reference types="Cypress" />

import EmailSettings from '../../elements/pages/settings/EmailSettings';
import MemberCreate from '../../elements/pages/members/MemberCreate';

const memberCreate = new MemberCreate
const emailSettings = new EmailSettings

const { _, $ } = Cypress

var userCount = 0;
var urlRegex = /(https?:\/\/[^\s]+)/g;

context('Member Registration on Front-end', () => {

    before(function() {
        cy.task('db:seed')
        cy.task('db:load', '../../support/sql/more-roles.sql')

        cy.eeConfig({ item: 'save_tmpl_files', value: 'y' })
        cy.eeConfig({ item: 'require_captcha', value: 'n' })
        cy.eeConfig({ item: 'allow_member_registration', value: 'y' })
        cy.eeConfig({ item: 'req_mbr_activation', value: 'none' })

        //copy templates
		cy.task('filesystem:copy', { from: 'support/templates/*', to: '../../system/user/templates/' }).then(() => {
            cy.authVisit('admin.php?/cp/design')
            cy.clearCookies()
        })
    })

    after(function() {
        cy.task('filesystem:delete', '../../system/user/templates/default_site/mbr.group')
        cy.eeConfig({ item: 'default_primary_role', value: '5' })
    })

    beforeEach(function() {
        userCount++;
    })

    afterEach(function() {
        //cy.clearCookies()
    })

    context('checks', function() {

        before(function(){
            cy.eeConfig({ item: 'password_security_policy', value: 'basic' })
        })

        it('password rank shown', function() {
            cy.clearCookies()
            cy.visit('index.php/mbr/register');
            cy.logFrontendPerformance()
            cy.intercept("**?ACT=**").as('ajax')
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('eee').blur();
            cy.wait('@ajax')
            cy.get('.rank-wrap').should('be.visible').should('contain', 'weak')
            cy.get('#password').clear().type('1Password').blur();
            cy.wait('@ajax')
            cy.get('.rank-wrap').should('be.visible').should('contain', 'good')
        })

        it('validates username', function() {
            cy.clearCookies()
            cy.visit('index.php/mbr/register');
            cy.logFrontendPerformance()
            cy.intercept("**?ACT=**").as('ajax')
            cy.get('#username').clear().type('user' + userCount);
            cy.get('a').contains('validate username').click()
            cy.wait('@ajax').its('response').then((res) => {
                // it is a good practice to add message argument to the
                // assertion "expect(value, message)..." that will be shown
                // in the test runner's command log
                expect(res.body.success, 'response body').to.equal(true)
            })

            cy.get('#username').clear().type('admin');
            cy.get('a').contains('validate username').click()
            cy.wait('@ajax').its('response').then((res) => {
                expect(res.body.success, 'response body').to.equal(false)
                expect(res.body.errors.username).to.contain('The username you chose is not available')
            })
        })

    })

    context('regular signup', function() {

        before(function(){
            cy.eeConfig({ item: 'password_security_policy', value: 'none' })
        })

        it('registers normally', {retries: 2}, function() {
            cy.clearCookies()
            cy.visit('index.php/mbr/register');
            cy.logFrontendPerformance()
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Members')
        })

        it('registers into unlocked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into locked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('registers into selected unlocked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register/6');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into selected locked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/7');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('cannot register into non-existing group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/143');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role does not exist')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('cannot register with weak password', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'password_security_policy', value: 'good' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('The chosen password is not secure enough')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('cannot register if passwords do not match', function() {
            cy.clearCookies()

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('anotherpassword');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('The password and password confirmation do not match')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('can register with good password', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'password_security_policy', value: 'good' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('1Password');
            cy.get('#password_confirm').clear().type('1Password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('exist')
        })

    })


    context('manual activation', function() {

        before(function(){
            cy.eeConfig({ item: 'req_mbr_activation', value: 'manual' })
            cy.eeConfig({ item: 'password_security_policy', value: 'none' })

            //register admin member in CP
            cy.auth()
            memberCreate.load()
            memberCreate.get('username').clear().type('memberadmin')
            memberCreate.get('email').clear().type('memberadmin@expressionengine.com')
            memberCreate.get('password').clear().type('1Password')
            memberCreate.get('confirm_password').clear().type('1Password')
            cy.get('[name=verify_password]').clear().type('password')
            cy.get('.tab-bar__tab:contains("Roles")').click()
            cy.get('[name=role_id]').check('7')
            cy.get('body').type('{ctrl}', {release: false}).type('s')
            cy.logout()
        })

        it('registers normally', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '5' })
            cy.clearCookies()
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Members')
        })

        it('registers into unlocked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into locked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('registers into selected unlocked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register/6');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into selected locked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/7');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('cannot register into non-existing group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/143');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role does not exist')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('approving moves into the group that existed when they registered', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.eeConfig({ item: 'default_primary_role', value: '7' })
            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('approving old members moves into the default group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('unable to approve old members if default group is locked', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.auth({
                email: 'memberadmin',
                password: '1Password'
            })

            cy.visit('admin.php?/cp/members');
            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Unable to activate");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4)').should('not.contain', 'Unlocked Extra Role')

        })

        it('superadmin can approve even if default group is locked', function() {

            cy.auth()
            cy.visit('admin.php?/cp/members');
            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4)').contains('Locked Role')

        })

    })

    context('email activation', function() {

        before(function(){
            cy.eeConfig({ item: 'req_mbr_activation', value: 'email' })
            cy.eeConfig({ item: 'password_security_policy', value: 'none' })
            cy.authVisit('admin.php?/cp/settings/email')

            emailSettings.get('mail_format').filter('[value=html]').check()
            emailSettings.get('mail_protocol').filter('[value=smtp]').check()
            emailSettings.get('email_newline').eq(1).check()
            emailSettings.get('smtp_server').clear().type('localhost')
            emailSettings.get('smtp_port').clear().type('1025')
            emailSettings.get('email_smtp_crypto').filter('[value=""]').check()
            cy.get('button').contains('Save Settings').first().click()
            cy.logout()
        })

        this.beforeEach(function(){
            cy.maildevDeleteAllMessages();
        })

        it('registers normally', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '5' })
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Members')
        })

        it('registers and auto logs in upon activation', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '5' })
            cy.eeConfig({ item: 'activation_auto_login', value: 'y' })
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('You are logged-in and ready to begin using your new account.');
                cy.clearCookies()
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Members')

            cy.eeConfig({ item: 'activation_auto_login', value: 'n' })
        })

        it('registers and redirects in upon activation', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '5' })
            cy.eeConfig({ item: 'activation_auto_login', value: 'y' })
            cy.eeConfig({ item: 'activation_redirect', value: '/mbr/profile-edit' })
            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.url().should('match', /mbr\/profile-edit/)
                cy.clearCookies()
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Members')

            cy.eeConfig({ item: 'activation_auto_login', value: 'n' })
            cy.eeConfig({ item: 'activation_redirect', value: '' })
        })

        it('registers into unlocked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into locked default group', function() {
            cy.clearCookies()
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('registers into selected unlocked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.visit('index.php/mbr/register/6');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('cannot register into selected locked group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/7');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role is locked')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('cannot register into non-existing group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register/143');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.contains('Unable to complete registration. The member role does not exist')
            cy.clearCookies()

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").should('not.exist')
        })

        it('approving moves into the group that existed when they registered', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.visit('index.php/mbr/register');
            cy.get('#username').clear().type('user' + userCount);
            cy.get('#email').clear().type('user' + userCount + '@expressionengine.com');
            cy.get('#password').clear().type('password');
            cy.get('#password_confirm').clear().type('password');
            cy.get('#accept_terms').check();
            cy.get('#submit').click();

            cy.get('h1').invoke('text').then((text) => {
                expect(text).equal('Member Registration Home')//redirected successfully
            })
            cy.clearCookies()

            cy.eeConfig({ item: 'default_primary_role', value: '7' })
            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) .st-pending').should('exist')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

    })


})
