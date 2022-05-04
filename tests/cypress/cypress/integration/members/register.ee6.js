/// <reference types="Cypress" />

import EmailSettings from '../../elements/pages/settings/EmailSettings';

const emailSettings = new EmailSettings

const { _, $ } = Cypress

var userCount = 0;

context('Member Registration', () => {

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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('approving old members moves into the default group', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '6' })

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Member Approved");

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending1')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

        it('unable to approve old members if default group is locked', function() {
            cy.eeConfig({ item: 'default_primary_role', value: '7' })

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.get("a:contains('pending2')").parents('tr').find('td:nth-child(4) a[title=Approve]').click()
            cy.contains("Unable to activate");

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

        })

        this.beforeEach(function(){
            cy.maildevDeleteAllMessages();
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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var urlRegex = /(http?:\/\/[^\s]+)/g;
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var urlRegex = /(http?:\/\/[^\s]+)/g;
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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                var urlRegex = /(http?:\/\/[^\s]+)/g;
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
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Pending')

            cy.clearCookies()
            cy.maildevGetAllMessages().then((emails) => {
                //var email = emails[emails.length - 1];
                var urlRegex = /(http?:\/\/[^\s]+)/g;
                var link = emails[0].text.match(urlRegex);
                cy.visit(link[0], {failOnStatusCode: false});
                cy.contains('Your account has been activated');
            });

            cy.authVisit('admin.php?/cp/members');
            cy.get("a:contains('user" + userCount + "')").parents('tr').find('td:nth-child(4)').contains('Unlocked Extra Role')
        })

    })


})
